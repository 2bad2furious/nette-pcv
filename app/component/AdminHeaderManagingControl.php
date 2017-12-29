<?php

//TODO rename
class AdminHeaderManagingControl extends BaseControl {
    public function render(HeaderPage $headerPage){
        $this->template->parent = $headerPage;
        $this->template->render();
    }

    public function handleMoveUp(int $id){

    }

    public function handleMoveDown(int $id){

    }

    public function handleDeleteAll(int $id){

    }

    public function handleDeleteSelf(int $id){

    }
}