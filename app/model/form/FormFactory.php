<?php

use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;

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

        SETTINGS_EDIT_DEFAULT_LANGUAGE_NAME = "default_language",
        SETTINGS_EDIT_DEFAULT_SITE_TITLE = "site_title",
        SETTINGS_EDIT_DEFAULT_GOOGLE_ANALYTICS = "ga",
        SETTINGS_EDIT_TITLE_SEPARATOR = "title_separator",
        SETTINGS_EDIT_LOGO = "logo",

        LANGUAGE_EDIT_CODE_NAME = "language_code",

        TEXTAREA_ID_FOR_EDITOR = "ckeditor";
    const LANGUAGE_EDIT_SITE_TITLE_NAME = "site_title";
    const LANGUAGE_EDIT_TITLE_SEPARATOR_NAME = "separator";
    const LANGUAGE_EDIT_LOGO_NAME = "logo";
    const LANGUAGE_EDIT_GOOGLE_ANALYTICS_NAME = "ga";
    const ONE_LINE_TEXTAREA_CLASS = "oneline";
    const LANGUAGE_EDIT_HOMEPAGE = "homepage_id";
    const LANGUAGE_EDIT_FAVICON_NAME = "favicon";
    const HEADER_PAGE_NAME = "page_id";
    const HEADER_TITLE_NAME = "title";
    const HEADER_URL_NAME = "url";
    const HEADER_SUBMIT_NAME = "submit";

    public function createPageEditForm(PageWrapper $page, callable $urlValidator) {

        $form = $this->createNewAdminForm();
        /*$form->getRenderer()->wrappers["group"]["container"] = "div";
        $form->getRenderer()->wrappers["group"]["label"] = "label";*/

        $container = $form->addContainer(self::PAGE_EDIT_GLOBAL_CONTAINER);
        $container->addSelect(self::PAGE_EDIT_GLOBAL_VISIBILITY_NAME, "admin.page.edit.global.visibility.label",
            [PageManager::STATUS_DRAFT => "admin.page.edit.global.visibility.draft", PageManager::STATUS_PUBLIC => "admin.page.edit.global.visibility.public"]
        )->setDefaultValue($page->getGlobalStatus());

        if ($page->isPage()) {
            $parents = array_merge([0 => "admin.page.edit.global.parent.no"], array_map(function (PageWrapper $page) {
                return $page->getTitle();
            }, $this->getPageManager()->getViableParents($page->getGlobalId(), $page->getLanguageId())));

            $container->addSelect(self::PAGE_EDIT_PARENT_NAME, "admin.page.edit.global.parent.label", $parents)
                ->setDefaultValue($page->getParentId());
        }

        $container = $form->addContainer(self::PAGE_EDIT_LOCAL_CONTAINER);
        $container->addSelect(self::PAGE_EDIT_LOCAL_VISIBILITY_NAME, "admin.page.edit.local.visibility.label",
            [PageManager::STATUS_DRAFT => "admin.page.edit.local.visibility.draft", PageManager::STATUS_PUBLIC => "admin.page.edit.local.visibility.public"]
        )->setDefaultValue($page->getLocalStatus());

        $container->addText(self::PAGE_EDIT_TITLE_NAME, "admin.page.edit.local.title.label")
            ->setDefaultValue($page->isTitleDefault() ? "" : $page->getTitle())
            ->addRule(Form::MAX_LENGTH, "admin.page.edit.local.title.length", PageManager::LOCAL_COLUMN_TITLE_LENGTH)
            ->addRule(Form::REQUIRED, "admin.page.edit.local.title.required");

        $container->addText(self::PAGE_EDIT_URL_NAME, "admin.page.edit.local.url.label")
            ->setDefaultValue($page->getCheckedUrl())
            ->addRule($urlValidator, "admin.page.edit.local.url.check.error_availability", "admin.page.edit.local.url.check.error_availability")
            ->addRule(Form::PATTERN, "admin.page.edit.local.url.check.pattern", PageManager::LOCAL_URL_CHARSET_FOR_ADMIN)
            ->addRule(Form::REQUIRED, "admin.page.edit.local.url.required")
            ->addRule(Form::MAX_LENGTH, "admin.page.edit.local.url.length", PageManager::LOCAL_COLUMN_URL_LENGTH);

        $container->addTextArea(self::PAGE_EDIT_DESCRIPTION_NAME, "admin.page.edit.local.description.label")
            ->addRule(Form::MAX_LENGTH, "admin.page.edit.local.description.length_error", PageManager::LOCAL_COLUMN_DESCRIPTION_LENGTH)
            ->setRequired(false)
            ->setDefaultValue($page->getDescription());

        $container->addTextArea(self::PAGE_EDIT_CONTENT_NAME, "admin.page.edit.local.content")
            ->setDefaultValue($page->getContent())
            ->getControlPrototype()->data(self::TEXTAREA_ID_FOR_EDITOR, true);

        $container->addSelect(self::PAGE_EDIT_IMAGE_NAME, "admin.page.edit.local.image.label", array_merge([0 => "admin.page.edit.local.image.no"], $this->getMediaManager()->getAvailableImages()))->setDefaultValue($page->getImageId());

        //todo add tag editing - not "in form"

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

    public function createAdminPageSearch(?string $query): Form {
        $form = $this->createNewAdminForm();
        $form->setMethod("get");
        $form->addText(self::PAGE_SHOW_SEARCH_NAME)->setRequired(false)->setDefaultValue($query);
        $form->addSubmit("submit", "admin.page.show.search.submit");
        return $form;
    }

    public function createLanguageEditForm(Language $language): Form {
        $form = $this->createNewAdminForm();

        $code = $form->addText(self::LANGUAGE_EDIT_CODE_NAME, "admin.language.code.label")
            ->addRule(Form::REQUIRED, "admin.language.edit.code.required")
            ->addRule(Form::MAX_LENGTH, "admin.language.edit.code.length", 5)
            ->addRule(Form::PATTERN, "admin.language.edit.code.pattern", LanguageManager::COLUMN_CODE_PATTERN)
            ->addRule(function (TextInput $item) {
                return !$this->getLanguageManager()->getByCode($item->getValue()) instanceof Language;
            }, $message = "admin.language.edit.code.not_available", $message);
        if (!LanguageManager::isCodeGenerated($language->getCode())) {
            $code->setDisabled(true)->setEmptyValue($language->getCode())
                ->setOmitted(false);
        }

        if (!LanguageManager::isCodeGenerated($language->getCode())) {
            $pm = $this->getPageManager();
            $allPages = $pm->getAllPages($language->getId());
            $homePageSelection = $form->addSelect(self::LANGUAGE_EDIT_HOMEPAGE, "admin.language.edit.homepage.label", array_map(function (PageWrapper $page) {
                return $page->getTitle();
            }, $allPages));

            $currentHomePage = $pm->getHomePage($language->getId());
            if ($currentHomePage instanceof PageWrapper) $homePageSelection->setDefaultValue($currentHomePage->getGlobalId());
        }

        $form->addText(self::LANGUAGE_EDIT_SITE_TITLE_NAME, "admin.language.edit.site_title.label")
            ->setDefaultValue($this->getSettingsManager()->get(PageManager::SETTINGS_SITE_NAME, $language->getId())->getValue());

        $form->addTextArea(self::LANGUAGE_EDIT_TITLE_SEPARATOR_NAME, "admin.language.edit.separator.label")
            ->setDefaultValue($this->getSettingsManager()->get(PageManager::SETTINGS_SITE_NAME, $language->getId())->getValue())
            ->getControlPrototype()->class(self::ONE_LINE_TEXTAREA_CLASS);

        $sm = $this->getSettingsManager();

        $images = $this->getMediaManager()->getAvailableImages();
        $images[0] = "admin.language.edit.logo.no";
        $form->addSelect(self::LANGUAGE_EDIT_LOGO_NAME, "admin.language.edit.logo.label", $images)->setDefaultValue((int)$this->getSettingsManager()->get(PageManager::SETTINGS_LOGO, $language->getId())->getValue());

        $images[0] = "admin.language.edit.favicon.no";
        $form->addSelect(self::LANGUAGE_EDIT_FAVICON_NAME, "admin.settings.edit.favicon", $images)->setDefaultValue((int)$this->getSettingsManager()->get(PageManager::SETTINGS_FAVICON, $language->getId())->getValue());

        $form->addText(self::LANGUAGE_EDIT_GOOGLE_ANALYTICS_NAME, "admin.language.edit.ga.label")
            ->setDefaultValue($this->getSettingsManager()->get(PageManager::SETTINGS_GOOGLE_ANALYTICS, $language->getId())->getValue());

        $form->addSubmit("submit", "admin.language.edit.submit");
        return $form;
    }

    public function createSettingsEditForm(): Form {
        $sm = $this->getSettingsManager();
        $lm = $this->getLanguageManager();
        $form = $this->createNewAdminForm();

        $form->addText(self::SETTINGS_EDIT_DEFAULT_SITE_TITLE, "admin.settings.edit.title.label")
            ->setDefaultValue($sm->get(PageManager::SETTINGS_SITE_NAME,null)->getValue());

        $form->addTextArea(self::SETTINGS_EDIT_TITLE_SEPARATOR, "admin.settings.edit.separator.label")
            ->setDefaultValue($sm->get(PageManager::SETTINGS_TITLE_SEPARATOR,null)->getValue())
            ->getControlPrototype()->class(self::ONE_LINE_TEXTAREA_CLASS);

        $availableLanguages = array_map(function (Language $language) {
            return $language->getCode();
        }, $lm->getAvailableLanguages());
        $form->addSelect(self::SETTINGS_EDIT_DEFAULT_LANGUAGE_NAME, "admin.settings.edit.language.label", $availableLanguages)
            ->setDefaultValue($lm->getDefaultLanguage()->getId());

        $images = $this->getMediaManager()->getAvailableImages();
        $images[0] = "admin.settings.edit.image.no";
        $logo = $form->addSelect(self::SETTINGS_EDIT_LOGO, "admin.settings.edit.logo.label", $images);
        if ($logoId = intval($sm->get(PageManager::SETTINGS_LOGO,null)->getValue()))
            $logo->setDefaultValue($logoId);


        $form->addText(self::SETTINGS_EDIT_DEFAULT_GOOGLE_ANALYTICS, "admin.settings.edit.ga.label")
            ->setDefaultValue($sm->get(PageManager::SETTINGS_GOOGLE_ANALYTICS,null)->getValue());

        $form->addSubmit("submit", "admin.settings.edit.save");
        return $form;
    }

    public function createLanguageSearchForm(?string $query) {
        $form = $this->createNewAdminForm();
        $form->setMethod("get");
        $form->addText(\adminModule\LanguagePresenter::SEARCH_KEY)->setRequired(false)->setDefaultValue($query);

        $form->addSubmit("submit", "admin.language.default.search.submit");
        return $form;
    }

    public function createHeaderPageEditForm(int $languageId, ?HeaderWrapper $headerWrapper): Form {
        if ($headerWrapper instanceof HeaderWrapper && $headerWrapper->getLanguageId() !== $languageId) throw new InvalidArgumentException("Language {$languageId} is not the same as header's {$headerWrapper->getLanguageId()}");
        $form = $this->createNewAdminForm();

        $language = $headerWrapper instanceof HeaderWrapper ?
            $headerWrapper->getLanguage() :
            $this->getLanguageManager()->getById($languageId);

        $pages = $this->getPageManager()->getAllPages($language->getId());
        $pageSelection = $form->addSelect(self::HEADER_PAGE_NAME, "admin.header.edit.page.label", array_map(function (PageWrapper $page) {
            return $page->getTitle() . " " . $page->getGlobalId();
        }, $pages));
        if ($headerWrapper instanceof HeaderWrapper) $pageSelection->setDefaultValue($headerWrapper->getPageId());

        $title = $form->addText(self::HEADER_TITLE_NAME, "admin.header.edit.title.optional.label");
        if ($headerWrapper instanceof HeaderWrapper) $title->setDefaultValue((string)$headerWrapper->getTitle());

        $form->addSubmit(self::HEADER_SUBMIT_NAME, "admin.header." . ($headerWrapper instanceof HeaderWrapper ? "edit" : "add") . ".submit");

        return $form;
    }

    public function createHeaderCustomEditForm(?HeaderWrapper $headerWrapper) {
        $form = $this->createNewAdminForm();

        $title = $form->addText(self::HEADER_TITLE_NAME, "admin.header.edit.title.required.label")
            ->addRule(Form::REQUIRED, "admin.header.edit.title.required.required");
        if ($headerWrapper instanceof HeaderWrapper) $title->setDefaultValue((string)$headerWrapper->getTitle());

        $url = $form->addText(self::HEADER_URL_NAME, "admin.header.edit.url.label")
            ->addRule(Form::URL, "admin.header.edit.url.pattern")
            ->addRule(Form::REQUIRED, "admin.header.url.required");
        if ($headerWrapper instanceof HeaderWrapper) $url->setDefaultValue($headerWrapper->getUrl());

        $form->addSubmit(self::HEADER_SUBMIT_NAME, "admin.header." . ($headerWrapper instanceof HeaderPage ? "edit" : "add") . ".submit");

        return $form;
    }
}