<?php

use Nette\Application\UI\Form;

class FormFactory extends Manager {

    const LOGIN_IDENTIFICATION_NAME = "login_identification",
        LOGIN_PASSWORD_NAME = "login_password",
        LOGIN_SUBMIT_NAME = "login_submit",

        PAGE_EDIT_GLOBAL_VISIBILITY_NAME = "visibility",
        PAGE_EDIT_LOCAL_VISIBILITY_NAME = "visibility",
        PAGE_EDIT_TITLE_NAME = "title",
        PAGE_EDIT_URL_NAME = "url",
        PAGE_EDIT_PARENT_NAME = "parent",
        PAGE_SHOW_SEARCH_NAME = "search_query",
        PAGE_EDIT_GLOBAL_CONTAINER = "global",
        PAGE_EDIT_LOCAL_CONTAINER = "local",
        PAGE_EDIT_CONTENT_NAME = "content",
        PAGE_EDIT_DESCRIPTION_NAME = "description",
        PAGE_EDIT_IMAGE_NAME = "image",

        TEXTAREA_ID_FOR_EDITOR = "ckeditor";

    public function createPageEditForm(Page $page, callable $urlValidator) {

        $form = $this->createNewAdminForm();
        /*$form->getRenderer()->wrappers["group"]["container"] = "div";
        $form->getRenderer()->wrappers["group"]["label"] = "label";*/

        //TODO drafting limitations
        $container = $form->addContainer(self::PAGE_EDIT_GLOBAL_CONTAINER);
        $container->addSelect(self::PAGE_EDIT_GLOBAL_VISIBILITY_NAME, "admin.page.edit.global.visibility.label",
            [PageManager::STATUS_DRAFT => "admin.page.edit.global.visibility.draft", PageManager::STATUS_PUBLIC => "admin.page.edit.global.visibility.public"]
        )->setDefaultValue($page->getGlobalStatus());

        if ($page->isPage()) {
            $parents = array_merge([0 => "admin.page.edit.global.parent.no"], $this->getPageManager()->getViableParents($page->getGlobalId(), $page->getLang()));
            foreach ($parents as $k => $v) {
                if ($v === "") $parents[$k] = "admin.global.page.no_title";
            }
            $container->addSelect(self::PAGE_EDIT_PARENT_NAME, "admin.page.edit.global.parent.label", $parents)
                ->setDefaultValue($page->getParentId());
        }

        $container = $form->addContainer(self::PAGE_EDIT_LOCAL_CONTAINER);
        $container->addSelect(self::PAGE_EDIT_LOCAL_VISIBILITY_NAME, "admin.page.edit.local.visibility.label",
            [PageManager::STATUS_DRAFT => "admin.page.edit.local.visibility.draft", PageManager::STATUS_PUBLIC => "admin.page.edit.local.visibility.public"]
        )->setDefaultValue($page->getLocalStatus());

        $container->addText(self::PAGE_EDIT_TITLE_NAME, "admin.page.edit.local.title.label")
            ->setDefaultValue($page->getTitle())
            ->addRule(Form::MAX_LENGTH, "admin.page.edit.local.title.length", PageManager::LOCAL_COLUMN_TITLE_LENGTH)
            ->addRule(Form::REQUIRED, "admin.page.edit.local.title.required");

        $container->addText(self::PAGE_EDIT_URL_NAME, "admin.page.edit.local.url.label")
            ->setDefaultValue($page->getCheckedUrl())
            ->addRule($urlValidator, "admin.page.edit.local.url.check.error_availability", "admin.page.edit.local.url.check.error_availability")
            ->addRule(Form::PATTERN, "admin.page.edit.local.url.check.pattern", "[0-9a-zA-Z-_+]+")
            ->addRule(Form::REQUIRED, "admin.page.edit.local.url.required")
            ->addRule(Form::MAX_LENGTH, "admin.page.edit.local.url.length", PageManager::LOCAL_COLUMN_URL_LENGTH);

        $container->addTextArea(self::PAGE_EDIT_DESCRIPTION_NAME, "admin.page.edit.local.description.label")
            ->addRule(Form::MAX_LENGTH, "admin.page.edit.local.description.length_error", PageManager::LOCAL_COLUMN_DESCRIPTION_LENGTH)
            ->setRequired(false)
            ->setDefaultValue($page->getDescription());

        $container->addTextArea(self::PAGE_EDIT_CONTENT_NAME, "admin.page.edit.local.content")
            ->setDefaultValue($page->getContent())
            ->getControlPrototype()->data(self::TEXTAREA_ID_FOR_EDITOR, true);

        $container->addSelect(self::PAGE_EDIT_IMAGE_NAME, "admin.page.edit.local.image.label", array_merge([0 => "admin.page.edit.local.image.no"], $this->getMediaManager()->getAvailableImages($page->getLang())));

        $form->addSubmit("submit", "admin.page.edit.action.edit");
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
        $form->getElementPrototype()->data("validation-mode", "live");
        return $form;
    }

    public function createLoginForm(): Form {
        $form = $this->createNewForm();
        $form->addText(self::LOGIN_IDENTIFICATION_NAME, "admin.login.form.identification");
        $form->addPassword(self::LOGIN_PASSWORD_NAME, "admin.login.form.password");
        $form->addSubmit(self::LOGIN_SUBMIT_NAME, "admin.login.form.submit");
        return $form;
    }

    public function createAdminPageSearch(?string $query) {
        $form = $this->createNewAdminForm();
        $form->setMethod("get");
        $form->addText(self::PAGE_SHOW_SEARCH_NAME)->setRequired(true)->setDefaultValue($query);
        $form->addSubmit("submit");
        return $form;
    }

}