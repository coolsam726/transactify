<?php

namespace Coolsam\Transactify\Support;

class Utils
{
    public function getClassNamespaceFromFile(string $classPath): string
    {
        try {
            $className = $this->getClassNameFromFile($classPath);
            $reflection = new \ReflectionClass($className);

            return $reflection->getNamespaceName();
        } catch (\ReflectionException $e) {
            // Handle the exception if the class does not exist or is not valid
            return '';
        }
    }

    public function getClassNameFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        if (preg_match('/namespace\s+(.+?);.*class\s+(\w+)/s', $contents, $matches)) {
            return $matches[1].'\\'.$matches[2];
        }

        return null;
    }
}
