<?php

class Node
{
    /** @var SplFileInfo */
    private $file;

    private $children = [];
    /**
     * @var string
     */
    private $root;

    public function __construct(SplFileInfo $file, ?string $root = null)
    {
        $this->file = $file;
        $this->setRoot($root);
    }

    private function setRoot(?string $root)
    {
        $imRoot = $root === null;

        if ($imRoot) {
            $this->root = $this->file->getRealPath();
            $this->addNodesByDirectory($this->root);
            return;
        }

        $this->root = $root;
        $this->addNodesByDirectory($this->file->getRealPath());
    }

    public function toMap(): array
    {
        $mapTree = [];

        /** @var Node $node */
        foreach ($this->flatten() as $node) {
            $node->enterInMap($mapTree);
        }

        return $mapTree;
    }

    private function passThroughToChildren(callable $fn)
    {
        $stack = $this->getChildren();

        while (count($stack)) {
            /** @var Node $node */
            $node = array_pop($stack);
            array_push($stack, ...$node->getChildren());
            $fn($node);
        }
    }

    private function flatten()
    {
        $flatten = [];
        $this->passThroughToChildren(function (Node $node) use (&$flatten) {
            $flatten[] = $node;
        });

        return $flatten;
    }

    private function getChildren(): array
    {
        return $this->children;
    }

    private static function makePathIfNotExists(&$current, $segment)
    {
        if (isset($current[$segment])) {
            return;
        }
        $current[$segment] = [];
    }

    private function keySegments()
    {
        $directories = explode(DIRECTORY_SEPARATOR, $this->getKey());

        $directories = array_filter($directories, function (string $segment) {
            return $segment !== '';
        });

        return array_filter($directories, function (string $segment) {
            return $segment !== $this->file->getFilename();
        });
    }

    private function enterInMap(array &$map)
    {
        $current = &$map;

        foreach ($this->keySegments() as $segment) {
            self::makePathIfNotExists($current, $segment);
            $current =  &$current[$segment];
        }

        if ($this->file->isDir()) {
            return;
        }

        $current[] = $this->file->getFilename();
    }

    private function addChildren(SplFileInfo $fileInRoot)
    {


        $this->children[] = new Node($fileInRoot, $this->root);
    }

    private function addNodesByDirectory(string $directory)
    {
        if ($this->file->isFile()) {
            return;
        }

        if ($directory === '') {
            return;
        }

        foreach (new FilesystemIterator($directory) as $file) {
            $this->addChildren($file);
        }
    }

    private function getKey()
    {
        return str_replace($this->root, '', $this->file->getRealPath());
    }

}
