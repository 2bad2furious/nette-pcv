<?php


namespace adminModule;


use Language;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
use Tracy\Debugger;

class HeaderPresenter extends AdminPresenter {


    const LANGUAGE_KEY = "language",
        ID_KEY = "id",
        TYPE_KEY = "form-type";

    const TYPE_PAGE = "page",
        TYPE_CUSTOM = "custom",
        DEFAULT_TYPE = self::TYPE_PAGE,
        TYPES = [\HeaderPage::TYPE_PAGE   => self::TYPE_PAGE,
                 \HeaderPage::TYPE_CUSTOM => self::TYPE_CUSTOM];

    /** @persistent */
    public $language;

    /** @persistent */
    public $id;
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
        $this->template->action = in_array(($action = $this->getAction()), ["add", "edit"]) ? $action : null;
        $this->template->header = $this->getHeaderManager()->getRoot($this->getCurrentLanguage(), null);
        $this->template->formType = $this->getFormType();
        $this->template->id = $this->getParameter("id");
    }

    private function getCurrentLanguage(): Language {
        $code = $this->getParameter(self::LANGUAGE_KEY);
        return $this->getLanguageManager()->getByCode($code);
    }

    public function actionAdd() {
        $id = $this->getIdParam();

        $this->headerPage = $id === 0 ?
            $this->getHeaderManager()->getRoot($this->getCurrentLanguage(), null) :
            $this->getHeaderManager()->getById($id);

        if (!$this->headerPage instanceof \HeaderPage) {
            $this->addError("admin.header.add.not_found");
            $this->redirect(302, ":default");
        }

        $this->setView("default");
        $this->redrawControl("edit-form");
    }

    public function actionEdit() {
        $this->headerPage = $this->getHeaderPageByIdParam();

        if (!$this->headerPage instanceof \HeaderPage || $this->headerPage->getLanguageId() !== $this->getCurrentLanguage()->getId()) {
            dump($this->headerPage, $this->getCurrentLanguage());
            $this->addError("admin.header.edit.not_found");
            $this->redirect(302, ":default");
        }

        $this->setView("default");
        $this->redrawControl("edit-form");
    }

    private function getHeaderPageByIdParam():?\HeaderPage {
        $id = $this->getIdParam();

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
            ->setAction($this->link("add", [self::TYPE_KEY => self::TYPE_PAGE]));

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
        $link = $this->link("add", [
            self::TYPE_KEY => self::TYPE_CUSTOM,
            self::ID_KEY   => $this->headerPage->getHeaderPageId(),
        ]);

        $form = $this->getFormFactory()->createHeaderCustomEditForm(null)
            ->setAction($link);

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
        $form->setAction($this->link("edit", [self::TYPE_KEY => self::TYPE_PAGE]));
        return $form;
    }

    public function createComponentHeaderCustomEditForm() {
        $headerPage = $this->headerPage;

        $form = $this->getFormFactory()->createHeaderCustomEditForm($headerPage);
        $form->setAction($this->link("edit", [self::TYPE_KEY => self::TYPE_CUSTOM]));
        return $form;
    }

    private function getFormType(): bool {
        return $this->headerPage instanceof \HeaderPage ?
            is_int($this->headerPage->getPageId()) :
            $this->getParameter(self::TYPE_KEY, self::TYPE_PAGE) === self::TYPE_PAGE;
    }

    private function getIdParam():?int {
        $id = $this->getParameter(self::ID_KEY);
        if (is_null($id)) $this->error("Id not found");
        return (int)$id;
    }
}