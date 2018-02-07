<?php


namespace adminModule;


class SliderPresenter extends AdminPresenter {

    const PAGE_KEY = "page";
    const ID_KEY = "id";
    private $numOfPages;

    protected function getAllowedRoles(): array {
        return \IAccountManager::ROLES_PAGE_MANAGING;
    }

    public function renderDefault() {
        $this->template->sliders = $this->getSliderManager()->getAllSliders(0, null, $this->getCurrentPage(), 5, $this->numOfPages);

        $this->checkPaging($this->getCurrentPage(), $this->numOfPages, self::PAGE_KEY);
    }

    private function getCurrentPage(): int {
        return $this->getParameter(self::PAGE_KEY, 1);
    }

    public function createComponentSliderAddForm(): \Form {
        $form = $this->getFormFactory()->createSliderAddForm();
        $form->onSuccess[] = function (\Form $form, array $values) {
            /** @var \SliderWrapper $slider */
            $slider = $this->commonTryCall(function () use ($values) {
                return $this->getSliderManager()->createNewSlider(
                    $values[\FormFactory::SLIDER_LANG_NAME],
                    $values[\FormFactory::SLIDER_TITLE_NAME]
                );
            });
            $this->redirect(302, "edit", [self::ID_KEY => $slider->getId()]);
        };
        return $form;
    }

    public function handleDelete(int $id) {
        if (!$this->getSliderManager()->sliderExists($id)) {
            $this->addWarning("admin.slider.delete.not_exist");
        } else $this->commonTryCall(function () use ($id) {
            $this->getSliderManager()->deleteSlider($id);
        });
        $this->redirect(302, "default");
    }
}