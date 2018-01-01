<?php

//TODO rename
class AdminHeaderManagingControl extends BaseControl {
    public function render(array $header) {
        $this->template->header = $header;
        $this->template->render();
    }

    public function handleMoveUp(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->getPresenter()->addError("admin.header.moveUp.not_found");

        if (!$this->getHeaderManager()->canBeMovedUp($id)) return $this->getPresenter()->addError("admin.header.moveUp.cannot");

        $this->getPresenter()->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveUp($id);
        });
    }

    public function handleMoveLeft(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->getPresenter()->addError("admin.header.moveLeft.not_found");

        if (!$this->getHeaderManager()->canBeMovedLeft($id)) return $this->getPresenter()->addError("admin.header.moveLeft.cannot");

        $this->getPresenter()->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveLeft($id);
        });
    }

    public function handleMoveRight(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->getPresenter()->addError("admin.header.moveRight.not_found");

        if (!$this->getHeaderManager()->canBeMovedRight($id)) return $this->getPresenter()->addError("admin.header.moveRight.cannot");

        $this->getPresenter()->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveRight($id);
        });
    }

    public function handleMoveDown(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->getPresenter()->addError("admin.header.moveDown.not_found");

        if (!$this->getHeaderManager()->canBeMovedDown($id)) return $this->getPresenter()->addError("admin.header.moveDown.cannot");

        $this->getPresenter()->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->moveDown($id);
        });
    }

    public function handleDeleteAll(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->getPresenter()->addError("admin.header.deleteAll.not_found");

        $this->getPresenter()->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->deleteBranch($id);
        });

        $this->redrawControl("header-edit");
    }

    public function handleDeleteSelf(int $id) {
        if (!$this->getHeaderManager()->exists($id)) return $this->getPresenter()->addError("admin.header.deleteSelf.not_found");

        $this->getPresenter()->commonTryCall(function () use ($id) {
            $this->getHeaderManager()->delete($id);
        });

        $this->redrawControl("header-edit");
    }
}