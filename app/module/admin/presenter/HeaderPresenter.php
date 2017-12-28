<?php


namespace adminModule;


use Language;
use Nette\Application\UI\Form;
use Tracy\Debugger;

class HeaderPresenter extends AdminPresenter {


    const LANGUAGE_KEY = "language",
        ID_KEY = "id",
        TYPE_KEY = "form-type";

    const TYPE_PAGE = true,
        TYPE_CUSTOM = false;

    /** @persistent */
    public $language;
    /**
     * @var \HeaderPage
     */
    private $headerPage;

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_PAGE_MANAGING;
    }

    public function actionDefault() {
        if (!$this->getParameter(self::LANGUAGE_KEY))
            $this->redirect(302, "this",
                [self::LANGUAGE_KEY => $this->getLanguageManager()->getDefaultLanguage()->getCode()]);
    }

    public function renderDefault() {
        $this->template->language = $this->getCurrentLanguage();
        $this->template->languages = $this->getLanguageManager()->getAvailableLanguages(true);
        $this->template->signal = $this->getSignalName();
        $this->template->header = $this->getHeaderManager()->getRoot($this->getCurrentLanguage(), null);
        $this->template->formType = $this->getFormType();
        $this->template->id = $this->getParameter("id");

    }

    private function getCurrentLanguage(): Language {
        $code = $this->getParameter(self::LANGUAGE_KEY);
        return $this->getLanguageManager()->getByCode($code);
    }

    /**
     * @param int $id of the parent
     */
    public function handleAdd(int $id) {
        $this->headerPage = $id === 0 ?
            $this->getHeaderManager()->getRoot($this->getCurrentLanguage(), null) :
            $this->getHeaderManager()->getById($id);

        if (!$this->headerPage instanceof \HeaderPage) {
            $this->addError("admin.header.add.not_found");
            $this->redirect(302, ":default");
        }

        $this->redrawControl("edit-form");
    }

    public function handleEdit(int $id) {
        $this->headerPage = $this->getHeaderManager()->getById($id);

        if (!$this->headerPage instanceof \HeaderPage) {
            $this->addError("admin.header.edit.not_found");
            $this->redirect(302, ":default");
        }

        $this->redrawControl("edit-form");
    }

    public function handleDelete(int $id) {
        try {
            $this->getHeaderManager()->delete($id);
        } catch (\Exception $ex) {
            $this->somethingWentWrong();
        }
    }

    public function handleMoveUp(int $id) {

    }

    public function handleMoveDown(int $id) {

    }

    public function handleDeleteAll(int $id) {

    }

    public function handleDeleteSelf(int $id) {

    }

    public function createComponentHeaderPageAddForm() {
        $form = $this->getFormFactory()->createHeaderPageEditForm($this->getCurrentLanguage(), null)
            ->setAction($this->link("this", [self::TYPE_KEY => self::TYPE_PAGE]));

        $form->onSuccess[] = function (Form $form, array $values) {
            try {
                $this->getHeaderManager()->addPage(
                    $this->headerPage,
                    $values[\FormFactory::HEADER_PAGE_NAME],
                    $values[\FormFactory::HEADER_TITLE_NAME]
                );
                $this->redirect(302, "default");
            } catch (\Exception $exception) {
                Debugger::log($exception);
                $this->somethingWentWrong();
            }
        };
        return $form;
    }

    public function createComponentHeaderCustomAddForm() {
        $link = $this->link("add!", [
            self::TYPE_KEY => self::TYPE_CUSTOM,
            self::ID_KEY   => $this->headerPage->getHeaderPageId(),
        ]);
        dump($link);

        $form = $this->getFormFactory()->createHeaderCustomEditForm(null)
            ->setAction($link);

        dump($form->getAction());

        $form->onSuccess[] = function (Form $form, array $values) {
            try {
                $this->getHeaderManager()->addCustom(
                    $this->headerPage,
                    $values[\FormFactory::HEADER_TITLE_NAME],
                    $values[\FormFactory::HEADER_URL_NAME]
                );
                $this->redirect(302, "default");
            } catch (\Exception $exception) {
                Debugger::log($exception);
                $this->somethingWentWrong();
                throw $exception;
            }
        };
        return $form;
    }

    public function createComponentHeaderPageEditForm() {
        $headerPage = $this->headerPage;

        $form = $this->getFormFactory()->createHeaderPageEditForm($this->getCurrentLanguage(), $headerPage);
        $form->setAction($this->link("this", [self::TYPE_KEY => self::TYPE_PAGE]));
        return $form;
    }

    public function createComponentHeaderCustomEditForm() {
        $headerPage = $this->headerPage;

        $form = $this->getFormFactory()->createHeaderCustomEditForm($headerPage);
        $form->setAction($this->link("this", [self::TYPE_KEY => self::TYPE_CUSTOM]));
        return $form;
    }

    private function getFormType(): bool {
        return $this->headerPage instanceof \HeaderPage ?
            is_int($this->headerPage->getPageId()) :
            $this->getParameter(self::TYPE_KEY, self::TYPE_PAGE) === self::TYPE_PAGE;
    }
}