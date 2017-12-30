<?php

//TODO rename
class AdminHeaderManagingControl extends BaseControl {
    public function render(array $header){
        $this->template->header = $header;
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