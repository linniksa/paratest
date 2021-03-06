<?php

declare(strict_types=1);

namespace ParaTest\Coverage;

use SebastianBergmann\CodeCoverage\CodeCoverage;

class CoverageMerger
{
    /**
     * @var CodeCoverage
     */
    private $coverage = null;

    /**
     * @param CodeCoverage $coverage
     */
    private function addCoverage(CodeCoverage $coverage)
    {
        if (null === $this->coverage) {
            $this->coverage = $coverage;
        } else {
            $this->coverage->merge($coverage);
        }
    }

    /**
     * Returns coverage object from file.
     *
     * @param \SplFileObject $coverageFile coverage file
     *
     * @return CodeCoverage
     */
    private function getCoverageObject(\SplFileObject $coverageFile): CodeCoverage
    {
        if ('<?php' === $coverageFile->fread(5)) {
            return include $coverageFile->getRealPath();
        }

        $coverageFile->fseek(0);
        // the PHPUnit 3.x and below
        return unserialize($coverageFile->fread($coverageFile->getSize()));
    }

    /**
     * Adds the coverage contained in $coverageFile and deletes the file afterwards.
     *
     * @param string $coverageFile Code coverage file
     *
     * @throws \RuntimeException When coverage file is empty
     */
    public function addCoverageFromFile(string $coverageFile = null)
    {
        if ($coverageFile === null || !file_exists($coverageFile)) {
            return;
        }

        $file = new \SplFileObject($coverageFile);

        if (0 === $file->getSize()) {
            $extra = 'This means a PHPUnit process has crashed.';

            if (!\function_exists('xdebug_get_code_coverage')) {
                $extra = 'Xdebug is disabled! Enable for coverage.';
            }

            throw new \RuntimeException(
                "Coverage file {$file->getRealPath()} is empty. " . $extra
            );
        }

        $this->addCoverage($this->getCoverageObject($file));

        unlink($file->getRealPath());
    }

    /**
     * Get coverage report generator.
     *
     * @return CoverageReporterInterface
     */
    public function getReporter(): CoverageReporterInterface
    {
        return new CoverageReporter($this->coverage);
    }

    /**
     * Get CodeCoverage object.
     *
     * @return CodeCoverage
     */
    public function getCodeCoverageObject(): ?CodeCoverage
    {
        return $this->coverage;
    }
}
