<?php

use Nette\Application\UI\Form;
use Nette\DI\Container;

class FormFactory {

    const LOGIN_IDENTIFICATION_NAME = "login_identification",
        LOGIN_PASSWORD_NAME = "login_password",
        LOGIN_SUBMIT_NAME = "login_submit",

        PAGE_EDIT_GLOBAL_VISIBILITY_NAME = "visibility",
        PAGE_EDIT_LOCAL_VISIBILITY_NAME = "visibility",
        PAGE_EDIT_TITLE_NAME = "title",
        PAGE_EDIT_URL_NAME = "url",
        PAGE_EDIT_PARENT_NAME = "parent",
        PAGE_SHOW_SEARCH_NAME = "search_query",
        PAGE_EDIT_GLOBAL_CONTAINER = "global_co",
        PAGE_EDIT_CONTENT_NAME = "content",


        TEXTAREA_ID_FOR_EDITOR = "ckeditor";

    /** @var \Kdyby\Translation\Translator */
    private $translator;

    /** @var  Container */
    private $context;

    /** @var  PageManager */
    private $pageManager;

    /** @var  LanguageManager */
    private $languageManager;

    /**
     * FormFactory constructor.
     * @param Container $context
     */
    public function __construct(Container $context) {
        $this->context = $context;
    }


    public function createPageEditForm(Page $page) {

        $form = $this->createNewAdminForm();
        /*$form->getRenderer()->wrappers["group"]["container"] = "div";
        $form->getRenderer()->wrappers["group"]["label"] = "label";*/

        //TODO drafting limitations
        $container = $form->addContainer("global");
        $container->addSelect(self::PAGE_EDIT_GLOBAL_VISIBILITY_NAME, "admin.page.edit.global.visibility.label",
            [0 => "admin.page.edit.global.visibility.draft", 1 => "admin.page.edit.global.visibility.public"]
        )->setDefaultValue($page->getGlobalStatus());

        $parent = $page->getParent();
        if (!$parent instanceof Page && $page->getGlobalId() !== 1) throw new Exception("Page without a parent that's not homepage");
        $parentId = $parent instanceof Page ? $parent->getGlobalId() : 1;

        $container->addSelect(self::PAGE_EDIT_PARENT_NAME, "admin.page.edit.global.parent.label", $this->getPageManager()->getViableParents($page->getGlobalId(), $page->getLang()))
            ->setDefaultValue($parentId)
            ->setDisabled($page->getGlobalId() === 1);

        $container = $form->addContainer("local");
        $container->addSelect(self::PAGE_EDIT_LOCAL_VISIBILITY_NAME, "admin.page.edit.local.visibility.label",
            [0 => "admin.page.edit.local.visibility.draft", 1 => "admin.page.edit.local.visibility.public"]
        )->setDefaultValue($page->getLocalStatus());

        $container->addText(self::PAGE_EDIT_TITLE_NAME, "admin.page.edit.local.title")
            ->setDefaultValue($page->getTitle())
            ->setRequired();

        $container->addText(self::PAGE_EDIT_URL_NAME, "admin.page.edit.local.url")
            ->setEmptyValue($page->getUrl())
            ->setRequired($page->getGlobalId() !== 1)
            ->setDisabled($page->getGlobalId() === 1);

        //don't show random url => starts with PageManager::RANDOM_URL_PREFIX?

        $container->addTextArea(self::PAGE_EDIT_CONTENT_NAME, "admin.page.edit.local.content")
            ->setDefaultValue($page->getContent())
            ->getControlPrototype()->data(self::TEXTAREA_ID_FOR_EDITOR, true);

        $form->addSubmit("submit", "admin.page.edit.action.edit");
        $form->onSubmit[] = function () {
            diedump(func_get_args());
        };
        return $form;
    }

    private function createNewForm(): Form {
        $form = new Form();
        $form->setTranslator($this->getTranslator());
        return $form;
    }

    private function createNewAdminForm(): Form {
        $form = $this->createNewForm();
        $form->getElementPrototype()->class("admin-form");
        return $form;
    }

    public function createLoginForm(): Form {
        $form = $this->createNewForm();
        $form->addText(self::LOGIN_IDENTIFICATION_NAME, "admin.login.form.identification");
        $form->addPassword(self::LOGIN_PASSWORD_NAME, "admin.login.form.password");
        $form->addSubmit(self::LOGIN_SUBMIT_NAME, "admin.login.form.submit");
        return $form;
    }

    private function getTranslator(): \Kdyby\Translation\Translator {
        if (!$this->translator instanceof Kdyby\Translation\Translator) {
            $this->translator = $this->context->getByType(\Kdyby\Translation\Translator::class);
        }
        return $this->translator;
    }

    public function createAdminPageSearch(?string $query) {
        $form = $this->createNewAdminForm();
        $form->setMethod("get");
        $form->addText(self::PAGE_SHOW_SEARCH_NAME)->setRequired(true)->setDefaultValue($query);
        $form->addSubmit("submit");
        return $form;
    }

    private function getPageManager(): PageManager {
        if (!$this->pageManager instanceof PageManager) {
            $this->pageManager = $this->context->getByType(PageManager::class);
        }
        return $this->pageManager;
    }

    private function getLanguageManager(): LanguageManager {
        if (!$this->languageManager instanceof LanguageManager) {
            $this->languageManager = $this->context->getByType(LanguageManager::class);
        }
        return $this->languageManager;
    }

}