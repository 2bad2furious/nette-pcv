<?php


namespace adminModule;


use Language;
use Nette\Application\Request;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
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
        return \AccountManager::ROLES_PAGE_MANAGING;
    }


    /**
     * @throws \LanguageByIdNotFound
     */
    public function actionDefault() {
        if (!$this->getParameter(self::LANGUAGE_KEY))
            $this->redirect(302, "this",
                [self::LANGUAGE_KEY => $this->getLanguageManager()->getDefaultLanguage()->getCode()]);
    }

    /**
     * @throws \LanguageByIdNotFound
     * @throws \LanguageByCodeNotFound
     * @throws \InvalidState
     */
    public function renderDefault() {
        $this->template->language = $this->getCurrentLanguage();
        $this->template->languages = $this->getLanguageManager()->getAvailableLanguages();

        $this->template->header = $this->getHeaderManager()->getHeader($this->getCurrentLanguage()->getId());
        $this->template->formType = $this->getFormType();

        $this->template->id = $this->getIdParam();


        if ($this->getSignalName()) {
            $this->redrawHeaderPages();
        }
    }

    /**
     * @return Language
     * @throws \LanguageByCodeNotFound
     */
    private function getCurrentLanguage(): Language {
        $code = $this->getParameter(self::LANGUAGE_KEY);
        return $this->getLanguageManager()->getByCode($code);
    }

    /**
     * @throws \LanguageByCodeNotFound
     */
    public function actionAdd() {
        $id = $this->getIdParam();

        if ($id !== 0 && !$this->getHeaderManager()->exists($id, $this->getCurrentLanguage()->getId())) {
            $this->addError("admin.header.add.not_found");
            $this->redirect(302, ":default");
        }

        $this->setView("default");
        $this->redrawControl("edit-form");
    }

    /**
     * @throws \LanguageByCodeNotFound
     */
    public function actionEdit() {
        if (!$this->getHeaderManager()->exists($this->getIdParam(), $this->getCurrentLanguage()->getId())) {

            $this->addError("admin.header.edit.not_found");
            $this->redirect(302, ":default");
        }
        $this->headerWrapper = $this->getHeaderManager()->getById($this->getIdParam());

        $this->setView("default");
        $this->redrawControl("edit-form");
    }

    /**
     * @param string $id
     * @param string $parentId
     * @param string $position
     * @throws \Exception
     */
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
        });

        $this->redirect(302, "default");
    }

    public function createComponentHeaderPageAddForm() {
        $form = $this->getFormFactory()->createHeaderPageEditForm($this->getCurrentLanguage()->getId(), null)
            ->setAction($this->link("add", [self::TYPE_KEY => self::TYPE_PAGE]));

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->commonTryCall(function () use ($values) {
                $this->getHeaderManager()->addPage(
                    ($parentId = $this->getIdParam()) > 0 ? $parentId : null,
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
                    ($parentId = $this->getIdParam()) > 0 ? $parentId : null,
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

    private function getIdParam(): ?int {
        $id = $this->getParameter(self::ID_KEY);
        //  if (is_null($id)) $this->error("Id not found");
        return (int)$id;
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function handleMoveUp(int $id) {
        if (!$this->getHeaderManager()->exists($id)) {
            $this->addError("admin.header.moveUp.not_found");
            return;
        }
        if (!$this->getHeaderManager()->canBeMovedUp($id)) {
            $this->addError("admin.header.moveUp.cannot");
            return;
        }

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveUp($id);
        });
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function handleMoveLeft(int $id) {
        if (!$this->getHeaderManager()->exists($id)) {
            $this->addError("admin.header.not_found");
            return;
        }
        if (!$this->getHeaderManager()->canBeMovedLeft($id)) {
            $this->addError("admin.header.moveLeft.cannot");
            return;
        }

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveLeft($id);
        });
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function handleMoveRight(int $id) {
        if (!$this->getHeaderManager()->exists($id)) {
            $this->addError("admin.header.not_found");
            return;
        }

        if (!$this->getHeaderManager()->canBeMovedRight($id)) {
            $this->addError("admin.header.moveRight.cannot");
            return;
        }

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveRight($id);
        });
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function handleMoveDown(int $id) {
        if (!$this->getHeaderManager()->exists($id)) {
            $this->addError("admin.header.not_found");
            return;
        }
        if (!$this->getHeaderManager()->canBeMovedDown($id)) {
            $this->addError("admin.header.moveDown.cannot");
            return;
        }

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveDown($id);
        });
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function handleDeleteAll(int $id) {
        if (!$this->getHeaderManager()->exists($id)) {
            $this->addError("admin.header.deleteAll.not_found");
            return;
        }

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->deleteBranch($id);
        });

    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function handleDeleteSelf(int $id) {
        if (!$this->getHeaderManager()->exists($id)) {
            $this->addError("admin.header.deleteSelf.not_found");
            return;
        }

        $this->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->delete($id);
        });

    }

    private function redrawHeaderPages() {
        $this->redrawControl("header-area");
    }


    protected function setPageSubtitle(): string {
        return "admin." . $this->getPresenterShortname() . ".default.title";
    }

}