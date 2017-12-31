<?php


namespace adminModule;


use Language;
use Nette\Application\UI\Form;
use Tracy\Debugger;

class HeaderPresenter extends AdminPresenter {


    const LANGUAGE_KEY = "language",
        ID_KEY = "headerPageId",
        TYPE_KEY = "formType";

    const TYPE_PAGE = "page",
        TYPE_CUSTOM = "custom",
        DEFAULT_TYPE = self::TYPE_PAGE,
        TYPES = [\Header::TYPE_PAGE   => self::TYPE_PAGE,
                 \Header::TYPE_CUSTOM => self::TYPE_CUSTOM];

    /** @persistent */
    public $language;

    /** @persistent */
    public $headerPageId;

    /** @persistent */
    public $formType;

    private $headerWrapper;

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
        $this->template->header = $this->getHeaderManager()->getHeader($this->getCurrentLanguage());
        $this->template->formType = $this->getFormType();
        $this->template->id = $this->getIdParam();
    }

    private function getCurrentLanguage(): Language {
        $code = $this->getParameter(self::LANGUAGE_KEY);
        return $this->getLanguageManager()->getByCode($code);
    }

    public function actionAdd() {
        $id = $this->getIdParam();

        if ($id !== 0 && !$this->getHeaderManager()->exists($id, $this->getCurrentLanguage()->getId())) {
            $this->addError("admin.header.add.not_found");
            $this->redirect(302, ":default");
        }

        $this->setView("default");
        $this->redrawControl("edit-form");
    }

    public function actionEdit() {
        if (!$this->getHeaderManager()->exists($this->getIdParam(), $this->getCurrentLanguage()->getId())) {
            dump($this->getIdParam(), $this->getCurrentLanguage());
            $this->addError("admin.header.edit.not_found");
            $this->redirect(302, ":default");
        }
        $this->headerWrapper = $this->getHeaderManager()->getById($this->getIdParam());

        $this->setView("default");
        $this->redrawControl("edit-form");
    }

    public function createComponentHeaderPageAddForm() {
        $form = $this->getFormFactory()->createHeaderPageEditForm($this->getCurrentLanguage()->getId(), null)
            ->setAction($this->link("add", [self::TYPE_KEY => self::TYPE_PAGE]));

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->commonTryCall(function () use ($values) {
                $this->getHeaderManager()->addPage(
                    $this->getIdParam(),
                    $this->getCurrentLanguage()->getId(),
                    $values[\FormFactory::HEADER_PAGE_NAME],
                    $values[\FormFactory::HEADER_TITLE_NAME]
                );
            });
            $this->redirect(302, "default", [self::TYPE_KEY => null, self::ID_KEY => null]);
        };
        return $form;
    }

    public function createComponentHeaderCustomAddForm() {
        $link = $this->link("add", [
            self::TYPE_KEY => self::TYPE_CUSTOM,
        ]);

        $form = $this->getFormFactory()->createHeaderCustomEditForm(null)
            ->setAction($link);

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->commonTryCall(function () use ($values) {
                $this->getHeaderManager()->addCustom(
                    $this->getIdParam(),
                    $this->getCurrentLanguage()->getId(),
                    $values[\FormFactory::HEADER_TITLE_NAME],
                    $values[\FormFactory::HEADER_URL_NAME]
                );
            });
            $this->redirect(302, "default", [self::TYPE_KEY => null, self::ID_KEY => null]);
        };
        return $form;
    }

    public function createComponentHeaderPageEditForm() {
        $form = $this->getFormFactory()->createHeaderPageEditForm($this->getCurrentLanguage()->getId(), $this->headerWrapper);
        $form->setAction($this->link("edit", [self::TYPE_KEY => self::TYPE_PAGE]));
        $form->onSuccess[] = function (Form $form, array $values) {
            $this->commonTryCall(function () use ($values) {
                $this->getHeaderManager()->editPage(
                    $this->getIdParam(),
                    $values[\FormFactory::HEADER_PAGE_NAME],
                    $values[\FormFactory::HEADER_TITLE_NAME]
                );
            });
            $this->redirect(302, "default", [self::TYPE_KEY => null, self::ID_KEY => null]);
        };
        return $form;
    }

    public function createComponentHeaderCustomEditForm() {
        $form = $this->getFormFactory()->createHeaderCustomEditForm($this->headerWrapper);
        $form->setAction($this->link("edit", [self::TYPE_KEY => self::TYPE_CUSTOM]));
        $form->onSuccess[] = function (Form $form, array $values) {
            $this->commonTryCall(function () use ($values) {
                $this->getHeaderManager()->editCustom(
                    $this->getIdParam(),
                    $values[\FormFactory::HEADER_TITLE_NAME],
                    $values[\FormFactory::HEADER_URL_NAME]
                );
            });
        };
        return $form;
    }

    public function createComponentAdminHeaderManaging(string $name) {
        return new \AdminHeaderManagingControl($this, $name);
    }

    private function getFormType(): ?string {
        if ($this->headerWrapper instanceof \HeaderWrapper)
            return self::TYPES[$this->headerWrapper->getType()];

        $type = $this->getParameter(self::TYPE_KEY);
        /*if (!in_array($type, self::TYPES)) trigger_error("type $type not found");*/
        return $type;
    }

    private function getIdParam():?int {
        $id = $this->getParameter(self::ID_KEY);
        //  if (is_null($id)) $this->error("Id not found");
        return (int)$id;
    }
}