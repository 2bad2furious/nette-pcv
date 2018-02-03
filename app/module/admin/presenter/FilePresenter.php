<?php


namespace adminModule;


use Nette\Application\UI\Form;
use Nette\Http\FileUpload;

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
                return \IUserManager::ROLES_ADMINISTRATION;
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

        /**
         * @param Form $form
         * @param FileUpload[] $values
         * @throws \Exception
         */
        $form->onSuccess[] = function (Form $form, array $values) {
            $this->commonTryCall(function () use ($values) {
                $uploads = $values[\FormFactory::MEDIA_UPLOAD_NAME];
                $successfuls = $unsuccessfuls = [];
                /** @var FileUpload $upload */
                foreach ($uploads as $index => $upload) {
                    $sanitizedName = $upload->getSanitizedName();
                    if (strlen($sanitizedName) > $this->getFileManager()->getMaxNameLength()) {
                        $unsuccessfuls[$index] = $upload;
                        continue;
                    }

                    $this->getFileManager()->add($upload);
                    $successfuls[$index] = $upload;
                }
            });
            $this->postGet("this");
        };
        return $form;
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

    private function getPage(): int {
        return $this->getParameter(self::PAGE_KEY, 1);
    }

    public function createComponentPaginator(string $name): \PaginatorControl {
        return new \PaginatorControl($this, $name, self::PAGE_KEY, $this->getPage(), $this->numOfPages);
    }
}