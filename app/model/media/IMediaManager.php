<?php

interface IMediaManager {
    /**
     * @param bool $asObjects
     * @return array|Media[]
     */
    public function getAvailableImages($asObjects = false);

    public function getById(int $id, ?int $desiredType = null): ?Media;
}