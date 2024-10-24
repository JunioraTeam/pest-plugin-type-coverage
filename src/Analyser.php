<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Closure;

/**
 * @internal
 */
final class Analyser
{
    /**
     * Analyse the code's type coverage.
     *
     * @param  array<int, string>  $files
     * @param  Closure(Result): void  $callback
     */
    public static function analyse(array $files, Closure $callback): void
    {
        $cacheFilepath = '.pest_type_coverage_cache';

        $cache = is_file($cacheFilepath)
            ? unserialize(file_get_contents($cacheFilepath))
            : [];

        $testCase = new TestCaseForTypeCoverage;

        $cacheIsModified = false;
        foreach ($files as $file) {
            $fileHash = md5_file($file);
            if (array_key_exists($file, $cache) && $cache[$file]['hash'] === $fileHash) {
                $errors = $cache[$file]['errors'];
                $ignored = $cache[$file]['ignored'];
            } else {
                $errors = $testCase->gatherAnalyserErrors([$file]);
                $ignored = $testCase->getIgnoredErrors();
                $cache[$file] = [
                    'hash' => $fileHash,
                    'errors' => $errors,
                    'ignored' => $ignored,
                ];
                $cacheIsModified = true;
            }
            $testCase->resetIgnoredErrors();

            $callback(Result::fromPHPStanErrors($file, $errors, $ignored));
        }
        foreach (array_keys($cache) as $file) {
            if (! is_file($file)) {
                unset($cache[$file]);
            }
        }
        if ($cacheIsModified) {
            file_put_contents($cacheFilepath, serialize($cache));
        }
    }
}
