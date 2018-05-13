<?php


namespace adminModule;


use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Tracy\Debugger;

class FilePresenter extends AdminPresenter {

    const TYPE_KEY = "type",
        PAGE_KEY = "page";

    const TYPE_ALL = "all",
        TYPE_IMAGE = "image",
        TYPE_OTHER = "other";

    const TYPES = [
        self::TYPE_ALL   => null,
        self::TYPE_IMAGE => \IFileManager::TYPE_IMAGE,
        self::TYPE_OTHER => \IFileManager::TYPE_OTHER];

    private $numOfPages;


    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "default":
                return \IAccountManager::ROLES_ADMINISTRATION;
            case "all":
                return \IAccountManager::ROLES_ADMINISTRATION;
        }
    }

    public function actionDefault() {
        if ($this->getParameter("upload") && !$this->getSignalName()) {
            $this->addError("admin.file.error.max_post", null, ["maxPostSize" => ini_get("post_max_size")]);
            $this->redirect(302, "this", ["upload" => null]);
        }
    }

    /**
     * @throws \InvalidState
     */
    public function renderDefault() {
        $currentPage = $this->getPage();
        $this->template->files = $this->getFileManager()->getAll($this->getManagerType(), $currentPage, 10, $this->numOfPages);
        $this->checkPaging($currentPage, $this->numOfPages, self::PAGE_KEY);
    }

    public function createComponentMediaUploadForm() {
        $form = $this->getFormFactory()->createMediaUploadForm();

        $form->onValidate[] = function (Form $form) {

            $uploads = $form->getValues()[\FormFactory::MEDIA_UPLOAD_NAME];
            /** @var FileUpload $upload */
            foreach ($uploads as $upload) {
                $sanitizedName = $upload->getSanitizedName();
                if (!$upload->isOk())
                    $form->addError(
                        $this->translator->translate("admin.file.error.be_{$upload->getError()}", null, [
                            'fileName'    => $upload->getName(),
                            'maxFileSize' => ini_get('upload_max_filesize')/*ini_get('post_max_size')*/,
                        ]), false);

                if (strlen($sanitizedName) > $this->getFileManager()->getMaxNameLength())
                    $form->addError(
                        $this->translator->translate("admin.file.error.too_long", null, [
                            "length" => $this->getFileManager()->getMaxNameLength(),
                        ]), false);
            }
        };
        /**
         * @param Form $form
         * @param FileUpload[] $values
         * @throws \Exception
         */
        $form->onSuccess[] = function (Form $form, array $values) {
            $this->commonTryCall(function () use ($values) {
                $uploads = $values[\FormFactory::MEDIA_UPLOAD_NAME];

                /** @var FileUpload $upload */
                foreach ($uploads as $index => $upload) {
                    $this->getFileManager()->add($upload);
                }
            });
            $this->postGet("default", ["upload" => null]);
        };
        return $form->setAction($this->link("this", ["upload" => 1]));
    }

    /**
     * @return int|null
     * @throws \InvalidState
     */
    private function getManagerType(): ?int {
        return self::TYPES[$this->getType()];
    }

    /**
     * @return string
     * @throws \InvalidState
     */
    private function getType(): string {
        $type = $this->getParameter(self::TYPE_KEY, self::TYPE_ALL);
        if (!key_exists($type, self::TYPES))
            throw new \InvalidState("Type not found");

        return $type;
    }

    public function actionAll() {
        $this->sendJson(
            array_values(
                array_map(function (\Image $image) {
                    return [
                        "url" => "/" . $image->getWholeSrc(),
                    ];
                }, $this->getFileManager()->getAll(\IFileManager::TYPE_IMAGE, null, null, $var)
                )
            )
        );
    }

    private function getPage(): int {
        return $this->getParameter(self::PAGE_KEY, 1);
    }

    public function createComponentPaginator(string $name): \PaginatorControl {
        return new \PaginatorControl($this, $name, self::PAGE_KEY, $this->getPage(), $this->numOfPages);
    }
}