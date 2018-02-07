<?php

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

        TEXTAREA_CLASS_FOR_EDITOR = "froala";
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
    const LANGUAGE_EDIT_404 = "page404_id";
    const LANGUAGE_EDIT_FRIENDLY_NAME = "friendly";
    const MEDIA_UPLOAD_NAME = "upload";
    const PAGE_EDIT_DISPLAY_TITLE_NAME = "display_title";
    const PAGE_EDIT_DISPLAY_BREADCRUMBS = "display_bread";
    const SLIDER_TITLE_NAME = "title";
    const SLIDER_LANG_NAME = "lang_id";

    public function createPageEditForm(PageWrapper $page, callable $urlValidator) {

        $form = $this->createNewAdminForm();
        /*$form->getRenderer()->wrappers["group"]["container"] = "div";
        $form->getRenderer()->wrappers["group"]["label"] = "label";*/

        $container = $form->addContainer(self::PAGE_EDIT_GLOBAL_CONTAINER);
        $container->addSelect(
            self::PAGE_EDIT_GLOBAL_VISIBILITY_NAME,
            "admin.page.edit.global.visibility.label",
            [PageManager::STATUS_DRAFT  => $this->getTranslator()->translate("admin.page.edit.global.visibility.draft"),
             PageManager::STATUS_PUBLIC => $this->getTranslator()->translate("admin.page.edit.global.visibility.public")]
        )->setDefaultValue($page->getGlobalStatus());

        if ($page->isPage()) {
            $parents = [0 => $this->getTranslator()->translate("admin.page.edit.global.parent.no")] + array_map(function (PageWrapper $page) {
                    return $page->getGlobalId() . " " . ($page->isTitleDefault() ? "" : $page->getTitle());
                }, $this->getPageManager()->getViableParents($page->getGlobalId(), $page->getLanguageId()));

            $container->addSelect(
                self::PAGE_EDIT_PARENT_NAME,
                "admin.page.edit.global.parent.label",
                $parents)
                ->setDefaultValue($page->getParentId());
        }

        $container = $form->addContainer(self::PAGE_EDIT_LOCAL_CONTAINER);
        $container->addSelect(
            self::PAGE_EDIT_LOCAL_VISIBILITY_NAME,
            "admin.page.edit.local.visibility.label",
            [PageManager::STATUS_DRAFT  => $this->getTranslator()->translate("admin.page.edit.local.visibility.draft"),
             PageManager::STATUS_PUBLIC => $this->getTranslator()->translate("admin.page.edit.local.visibility.public")]
        )->setDefaultValue($page->getLocalStatus());

        $container->addText(
            self::PAGE_EDIT_TITLE_NAME,
            "admin.page.edit.local.title.label")
            ->setDefaultValue($page->isTitleDefault() ? "" : $page->getTitle())
            ->addRule(
                Form::MAX_LENGTH,
                "admin.page.edit.local.title.length",
                PageManager::LOCAL_COLUMN_TITLE_LENGTH)
            ->addRule(
                Form::REQUIRED,
                "admin.page.edit.local.title.required");

        $container->addText(
            self::PAGE_EDIT_URL_NAME,
            "admin.page.edit.local.url.label")
            ->setDefaultValue($page->getCheckedUrl())
            ->addRule(
                $urlValidator,
                "admin.page.edit.local.url.check.error_availability",
                "admin.page.edit.local.url.check.error_availability")
            ->addRule(
                Form::PATTERN,
                "admin.page.edit.local.url.check.pattern",
                PageManager::LOCAL_URL_CHARSET_FOR_ADMIN)
            ->addRule(
                Form::REQUIRED,
                "admin.page.edit.local.url.required")
            ->addRule(
                Form::MAX_LENGTH,
                "admin.page.edit.local.url.length",
                PageManager::LOCAL_COLUMN_URL_LENGTH);

        $container->addTextArea(
            self::PAGE_EDIT_DESCRIPTION_NAME,
            "admin.page.edit.local.description.label")
            ->addRule(
                Form::MAX_LENGTH,
                "admin.page.edit.local.description.length_error",
                PageManager::LOCAL_COLUMN_DESCRIPTION_LENGTH)
            ->setRequired(false)
            ->setDefaultValue($page->getDescription());

        $container->addTextArea(
            self::PAGE_EDIT_CONTENT_NAME,
            "admin.page.edit.local.content")
            ->setDefaultValue($page->getContent())
            ->getControlPrototype()->class(self::TEXTAREA_CLASS_FOR_EDITOR);

        $container->addSelect(
            self::PAGE_EDIT_IMAGE_NAME,
            "admin.page.edit.local.image.label",
            [0 => $this->getTranslator()->translate("admin.page.edit.local.image.no")] + array_map(function (File $media) {
                return $media->getSrc();
            }, $this->getMediaManager()->getAvailableImages()))
            ->setDefaultValue($page->getImageId());

        $title = $container->addCheckbox(self::PAGE_EDIT_DISPLAY_TITLE_NAME,
            "admin.page.edit.local.display.title.label")
            ->setDefaultValue($page->getDisplayBreadCrumbs());

        $checkbox = $title->getControlPrototype();
        $checkbox->data("off-text", $this->getTranslator()->translate("admin.page.edit.local.display.title.no"));
        $checkbox->data("on-text", $this->getTranslator()->translate("admin.page.edit.local.display.title.yes"));
        $checkbox->data("label-text", $this->getTranslator()->translate("admin.page.edit.local.display.title.label"));

        $breadCrumbs = $container->addCheckbox(self::PAGE_EDIT_DISPLAY_BREADCRUMBS,
            "admin.page.edit.local.display.breadcrumbs.label")
            ->setDefaultValue($page->getDisplayTitle());
        $checkbox = $breadCrumbs->getControlPrototype();

        $checkbox->data("off-text", $this->getTranslator()->translate("admin.page.edit.local.display.breadcrumbs.no"));
        $checkbox->data("on-text", $this->getTranslator()->translate("admin.page.edit.local.display.breadcrumbs.yes"));
        $checkbox->data("label-text", $this->getTranslator()->translate("admin.page.edit.local.display.breadcrumbs.label"));

        //todo add tag editing - not "in form"

        $form->addSubmit(
            "submit",
            "admin.page.edit.action.edit");
        return $form;
    }

    private function createNewForm(): Form {
        $form = new Form();
        $form->setTranslator($this->getTranslator());
        $form->getElementPrototype()->data("ajax", "false");//TODO do ajax forms
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
        $form->getElementPrototype()->class("form-inline");
        $form->setMethod("get");
        $form->addText(self::PAGE_SHOW_SEARCH_NAME)
            ->setRequired(false)
            ->setDefaultValue($query);
        $form->addSubmit(
            "submit",
            "admin.page.show.filter.search.submit");
        return $form;
    }

    public function createLanguageAddForm(): Form {
        $form = $this->createNewAdminForm();
        $form->addText(
            self::LANGUAGE_EDIT_CODE_NAME,
            "admin.language.code.label")
            ->addRule(
                Form::REQUIRED,
                "admin.language.edit.code.required")
            ->addRule(
                Form::MAX_LENGTH,
                "admin.language.edit.code.length",
                5)
            ->addRule(
                Form::PATTERN,
                "admin.language.edit.code.pattern",
                LanguageManager::COLUMN_CODE_PATTERN)
            ->addRule(
                function (TextInput $item): bool {
                    return !$this->getLanguageManager()->getByCode($item->getValue(), false) instanceof Language;
                },
                $message = "admin.language.edit.code.not_available",
                $message);

        $form->addText(
            self::LANGUAGE_EDIT_FRIENDLY_NAME,
            "admin.language.edit.friendly.label")
            ->addRule(
                Form::MAX_LENGTH,
                "admin.language.edit.friendly.length",
                LanguageManager::COLUMN_FRIENDLY_LENGTH)
            ->addRule(
                Form::REQUIRED,
                "admin.language.edit.friendly.required");

        $form->addText(
            self::LANGUAGE_EDIT_SITE_TITLE_NAME,
            "admin.language.edit.site_title.label")
            ->addRule(
                Form::MAX_LENGTH,
                "admin.language.edit.site_title.length",
                LanguageManager::COLUMN_SITE_NAME_LENGTH)
            ->addRule(
                Form::REQUIRED,
                "admin.language.edit.site_title.required");

        $form->addSubmit(
            "submit",
            "admin.language.add.submit");


        return $form;
    }

    public function createLanguageEditForm(Language $language): Form {
        $form = $this->createNewAdminForm();

        $pm = $this->getPageManager();

        $form->addText(
            self::LANGUAGE_EDIT_FRIENDLY_NAME,
            "admin.language.edit.friendly.label")
            ->setDefaultValue($language->getFriendly())
            ->addRule(
                Form::MAX_LENGTH,
                "admin.language.edit.friendly.length",
                LanguageManager::COLUMN_FRIENDLY_LENGTH)
            ->addRule(
                Form::REQUIRED,
                "admin.language.edit.friendly.required");

        $form->addText(self::LANGUAGE_EDIT_SITE_TITLE_NAME,
            "admin.language.edit.site_title.label")
            ->setDefaultValue($language->getSiteName())
            ->addRule(Form::MAX_LENGTH,
                "admin.language.edit.site_title.length",
                LanguageManager::COLUMN_SITE_NAME_LENGTH)
            ->addRule(Form::REQUIRED,
                "admin.language.edit.site_title.required");


        $form->addTextArea(
            self::LANGUAGE_EDIT_TITLE_SEPARATOR_NAME,
            "admin.language.edit.separator.label")
            ->addRule(Form::MAX_LENGTH, "admin.language.edit.separator.length", LanguageManager::COLUMN_TITLE_SEPARATOR_LENGTH)
            ->setDefaultValue($language->getTitleSeparator())
            ->setRequired(false)
            ->getControlPrototype()->class(self::ONE_LINE_TEXTAREA_CLASS);

        $images = array_map(function (Image $file) {
            return $file->getSrc();
        }, $this->getMediaManager()->getAvailableImages());


        $form->addSelect(self::LANGUAGE_EDIT_LOGO_NAME,
            "admin.language.edit.logo.label",
            [0 => $this->getTranslator()->translate("admin.language.edit.logo.no")] + $images)
            ->setDefaultValue($language->getLogoId());


        $form->addSelect(
            self::LANGUAGE_EDIT_FAVICON_NAME,
            "admin.language.edit.favicon.label",
            [0 => $this->getTranslator()->translate("admin.language.edit.favicon.no")] + $images)
            ->setDefaultValue($language->getFaviconId());

        $form->addText(self::LANGUAGE_EDIT_GOOGLE_ANALYTICS_NAME, "admin.language.edit.ga.label")
            ->addRule(Form::MAX_LENGTH, "admin.language.edit.ga.length")
            ->setRequired(false)
            ->setDefaultValue($language->getGa());

        $allPages = $pm->getAllPages($language->getId());
        $homePageSelection = $form->addSelect(
            self::LANGUAGE_EDIT_HOMEPAGE,
            "admin.language.edit.homepage.label",
            [0 => $this->getTranslator()->translate("admin.language.edit.homepage.no")] + array_map(function (PageWrapper $page) {
                return $page->getGlobalId() . " - " . $page->getTitle();
            }, $allPages)
        );


        $homePageSelection->setDefaultValue($language->getHomepageId());

        $form->addSelect(
            self::LANGUAGE_EDIT_404,
            "admin.language.edit.404.label",
            [0 => $this->getTranslator()->translate("admin.language.edit.404.no")] + array_map(function (PageWrapper $pageWrapper) {
                return $pageWrapper->getGlobalId() . " - " . $pageWrapper->getTitle();
            }, $allPages)
        );


        $form->addSubmit("submit", "admin.language.edit.submit");
        return $form;
    }

    /**
     * @return Form
     * @throws LanguageByIdNotFound
     */
    public function createSettingsEditForm(): Form {
        $sm = $this->getSettingsManager();
        $lm = $this->getLanguageManager();
        $form = $this->createNewAdminForm();

        $siteTitle = $sm->get(PageManager::SETTINGS_SITE_NAME, null)->getValue();
        $form->addText(
            self::SETTINGS_EDIT_DEFAULT_SITE_TITLE,
            "admin.settings.edit.title.label")
            ->addRule(Form::MAX_LENGTH, "admin.settings.edit.title.length", LanguageManager::COLUMN_SITE_NAME_LENGTH)
            ->setRequired(false)
            ->setDefaultValue($siteTitle);

        $titleSeparator = $sm->get(PageManager::SETTINGS_TITLE_SEPARATOR, null)->getValue();
        $form->addTextArea(
            self::SETTINGS_EDIT_TITLE_SEPARATOR,
            "admin.settings.edit.separator.label")
            ->setDefaultValue($titleSeparator)
            ->setRequired(false)
            ->addRule(Form::MAX_LENGTH, "admin.settings.edit.separator.length", LanguageManager::COLUMN_TITLE_SEPARATOR_LENGTH)
            ->getControlPrototype()->class(self::ONE_LINE_TEXTAREA_CLASS);

        $availableLanguages = array_map(function (Language $language) {
            return $language->getFriendly();
        }, $lm->getAvailableLanguages());
        $form->addSelect(
            self::SETTINGS_EDIT_DEFAULT_LANGUAGE_NAME,
            "admin.settings.edit.language.label",
            $availableLanguages)
            ->setDefaultValue($lm->getDefaultLanguage()->getId());

        $images = array_map(function (Image $img) {
            return $img->getSrc();
        }, $this->getMediaManager()->getAvailableImages());

        $logo = $form->addSelect(
            self::SETTINGS_EDIT_LOGO,
            "admin.settings.edit.logo.label",
            [0 => $this->getTranslator()->translate("admin.settings.edit.logo.no")] + $images);
        if ($logoId = intval($sm->get(PageManager::SETTINGS_LOGO, null)->getValue()))
            $logo->setDefaultValue($logoId);

        $ga = $sm->get(PageManager::SETTINGS_GOOGLE_ANALYTICS, null)->getValue();
        $form->addText(
            self::SETTINGS_EDIT_DEFAULT_GOOGLE_ANALYTICS,
            "admin.settings.edit.ga.label")
            ->setDefaultValue($ga)
            ->setRequired(false)
            ->addRule(Form::MAX_LENGTH, "admin.settings.edit.ga.length", LanguageManager::COLUMN_GA_LENGTH);

        $form->addSubmit("submit", "admin.settings.edit.save");
        return $form;
    }

    public function createLanguageSearchForm(?string $query) {
        $form = $this->createNewAdminForm();
        $form->setMethod("get");
        $form->addText(\adminModule\LanguagePresenter::SEARCH_KEY)
            ->setRequired(false)->setDefaultValue($query);

        $form->getElementPrototype()->class("form-inline");

        $form->addSubmit("submit", "admin.language.default.search.submit");
        return $form;
    }

    public function createHeaderPageEditForm(int $languageId, ?HeaderWrapper $headerWrapper): Form {
        if ($headerWrapper instanceof HeaderWrapper && $headerWrapper->getLanguageId() !== $languageId) throw new InvalidArgumentException("Language {$languageId} is not the same as header's {$headerWrapper->getLanguageId()}");
        $form = $this->createNewAdminForm();

        $pages = $this->getPageManager()->getAllPages($languageId);
        $pageSelection = $form->addSelect(self::HEADER_PAGE_NAME, "admin.header.edit.page.label", array_map(function (PageWrapper $page) {
            return $page->getGlobalId() . ($page->isTitleDefault() ? "" : " " . $page->getTitle());
        }, $pages));
        if ($headerWrapper instanceof HeaderWrapper)
            $pageSelection->setDefaultValue($headerWrapper->getPageId());

        $title = $form->addText(self::HEADER_TITLE_NAME, "admin.header.edit.title.optional.label");
        if ($headerWrapper instanceof HeaderWrapper)
            $title->setDefaultValue((string)$headerWrapper->getTitle());

        $form->addSubmit(
            self::HEADER_SUBMIT_NAME,
            "admin.header." . ($headerWrapper instanceof HeaderWrapper ? "edit" : "add") . ".page.submit");

        return $form;
    }

    public function createHeaderCustomEditForm(?HeaderWrapper $headerWrapper) {
        $form = $this->createNewAdminForm();
        dump($headerWrapper);
        $title = $form->addText(self::HEADER_TITLE_NAME, "admin.header.edit.title.required.label")
            ->addRule(Form::REQUIRED, "admin.header.edit.title.required.required");
        if ($headerWrapper instanceof HeaderWrapper) $title->setDefaultValue((string)$headerWrapper->getTitle());

        $url = $form->addText(self::HEADER_URL_NAME, "admin.header.edit.url.label")
            ->addRule(Form::URL, "admin.header.edit.url.pattern")
            ->addRule(Form::REQUIRED, "admin.header.edit.url.required");
        if ($headerWrapper instanceof HeaderWrapper) $url->setDefaultValue($headerWrapper->getUrl());

        $form->addSubmit(
            self::HEADER_SUBMIT_NAME,
            "admin.header." . ($headerWrapper instanceof HeaderPage ? "edit" : "add") . ".page.submit");

        return $form;
    }

    public function createMediaUploadForm(): Form {
        $form = $this->createNewAdminForm();
        $form->addMultiUpload(
            self::MEDIA_UPLOAD_NAME,
            "admin.media.default.upload.label");//TODO limit types by MIME

        $form->addSubmit(
            "submit",
            "admin.media.default.upload.label");
        return $form;
    }

    public function createSliderAddForm(): Form {
        $form = $this->createNewAdminForm();
        $form->addText(
            self::SLIDER_TITLE_NAME,
            "admin.form.slider.add.title.label")
            ->addRule(Form::MAX_LENGTH,
                "admin.form.slider.add.title.length",
                $this->getSliderManager()->getSliderTitleMaxLength())
            ->addRule(Form::REQUIRED,
                "admin.form.slider.add.title.required");
        $form->addSelect(self::SLIDER_LANG_NAME,
            "admin.form.slider.add.lang.label",
            [0 => $this->getTranslator()->translate("admin.form.slider.add.lang.no")] + array_map(function (Language $lang) {
                return $lang->getFriendly();
            }, $this->getLanguageManager()->getAvailableLanguages()));
        $form->addSubmit("submit", "admin.form.slider.add.submit");
        return $form;
    }
}