<?php


use Nette\Utils\DateTime;

class Change {
    /** @var  int */
    private $id;
    /** @var  int */
    private $pageId;
    /** @var  DateTime */
    private $created;
    /** @var  UserIdentity */
    private $authorId;
    /** @var  DateTime */
    private $verified;
    /** @var  UserIdentity */
    private $verificator;
    /** @var  Page */
    private $preChange;
    /** @var  Page */
    private $postChange;

    /**
     * Change constructor.
     * @param int $id
     * @param int $pageId
     * @param DateTime $created
     * @param UserIdentity $authorId
     * @param DateTime $verified
     * @param UserIdentity $verificator
     * @param Page|null $preChange
     * @param Page $postChange
     */
    public function __construct(int $id, int $pageId, DateTime $created, UserIdentity $authorId, DateTime $verified, UserIdentity $verificator, ?Page $preChange, Page $postChange) {
        $this->id = $id;
        $this->pageId = $pageId;
        $this->created = $created;
        $this->authorId = $authorId;
        $this->verified = $verified;
        $this->verificator = $verificator;
        $this->preChange = $preChange;
        $this->postChange = $postChange;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPageId(): int {
        return $this->pageId;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime {
        return $this->created;
    }

    /**
     * @return UserIdentity
     */
    public function getAuthorId(): UserIdentity {
        return $this->authorId;
    }

    /**
     * @return DateTime
     */
    public function getVerified(): DateTime {
        return $this->verified;
    }

    /**
     * @return UserIdentity
     */
    public function getVerificator(): UserIdentity {
        return $this->verificator;
    }

    /**
     * @return Page|null
     */
    public function getPreChange(): ?Page {
        return $this->preChange;
    }

    /**
     * @return Page
     */
    public function getPostChange(): Page {
        return $this->postChange;
    }
}