<?php


namespace adminModule;


use Language;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;

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
        $this->template->languages = $this->getLanguageManager()->getAvailableLanguages();
        $this->template->action = in_array(($action = $this->getAction()), ["add", "edit"]) ? $action : null;
        $this->template->header = $this->getHeaderManager()->getHeader($this->getCurrentLanguage()->getId());
        $this->template->formType = $this->getFormType();
        $this->template->id = $this->getIdParam();


        if ($this->getSignalName() || $this->isAjax()) {
            $this->redrawDefault();
        }

        if ($this->getSignalName()) {
            $this->redirect("default", [self::SIGNAL_KEY => null, "id" => null]);
        }
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

            $this->addError("admin.header.edit.not_found");
            $this->redirect(302, ":default");
        }
        $this->headerWrapper = $this->getHeaderManager()->getById($this->getIdParam());

        $this->setView("default");
        $this->redrawControl("edit-form");
    }

    public function handleChangeParent(string $id, string $parentId, string $position) {
        $headerId = (int)$id;
        $parentHeaderId = (int)$parentId;
        $headerPosition = (int)$position;

        $this->commonTryCall(function () use ($headerId, $parentHeaderId, $id, $headerPosition) {
            if (!$id) $this->error("Id $id not correct");

            if ($headerId === $parentHeaderId) $this->error("Header and parent cannot be same; $id", IResponse::S400_BAD_REQUEST);

            $curLang = $this->getCurrentLanguage();

            if (!$this->getHeaderManager()->exists($headerId, $curLang->getId()))
                $this->error("HeaderId $id not found", IResponse::S400_BAD_REQUEST);

            if ($parentHeaderId !== 0 && !$this->getHeaderManager()->exists($parentHeaderId, $curLang->getId()))
                $this->error("ParentHeaderId $parentHeaderId not found", IResponse::S400_BAD_REQUEST);

            $this->getHeaderManager()->changeParentOrPosition($headerId, $parentHeaderId, $headerPosition);
            $this->addSuccess("admin.header.change.parent.success");
        }, function () {
            $this->redrawControl("header-area");
            $this->redrawControl("header-edit-form-wrapper");
        });
        $this->redirect(302, "default");
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
            $this->redrawControl("header-area");
            $this->redrawControl("header-edit-form-wrapper");
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
            $this->redrawControl("header-area");
            $this->redrawControl("header-edit-form-wrapper");
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
            $this->redrawControl("header-area");
            $this->redrawControl("header-edit-form-wrapper");
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

    public function handleMoveUp(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->addError("admin.header.moveUp.not_found");

        if (!$this->getHeaderManager()->canBeMovedUp($id)) return $this->addError("admin.header.moveUp.cannot");

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveUp($id);
        });
    }

    public function handleMoveLeft(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->addError("admin.header.moveLeft.not_found");

        if (!$this->getHeaderManager()->canBeMovedLeft($id)) return $this->addError("admin.header.moveLeft.cannot");

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveLeft($id);
        });
    }

    public function handleMoveRight(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->addError("admin.header.moveRight.not_found");

        if (!$this->getHeaderManager()->canBeMovedRight($id)) return $this->addError("admin.header.moveRight.cannot");

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveRight($id);
        });
    }

    public function handleMoveDown(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->addError("admin.header.moveDown.not_found");

        dump($this->getHeaderManager()->canBeMovedDown($id));
        if (!$this->getHeaderManager()->canBeMovedDown($id)) return $this->addError("admin.header.moveDown.cannot");

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveDown($id);
        });
    }

    public function handleDeleteAll(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->addError("admin.header.deleteAll.not_found");

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->deleteBranch($id);
        });

    }

    public function handleDeleteSelf(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->addError("admin.header.deleteSelf.not_found");

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->delete($id);
        });

    }
}