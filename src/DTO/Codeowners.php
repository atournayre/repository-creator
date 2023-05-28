<?php

namespace App\DTO;

readonly class Codeowners
{
    private function __construct(
        private array $config,
    )
    {
    }

    public static function fromArray(array $config): self
    {
        return new self($config);
    }

    public function getCompiledPatterns(): array
    {
        $compiledPatterns = [];

        foreach ($this->getPatterns() as $pattern) {
            $compiledPatterns[$pattern['pattern']] = $this->compilePattern($pattern['owners']);
        }

        return $compiledPatterns;
    }

    private function getPatterns(): array
    {
        return $this->config['patterns'] ?? [];
    }

    private function compilePattern(array $owners): array
    {
        $finalOwners = [];
        foreach ($owners as $owner) {
            $currentOwners = $this->compileReviewers()[$owner] ?? $this->getDefaultOwners();
            $finalOwners = array_merge(
                $finalOwners,
                is_string($currentOwners) ? [$currentOwners] : ($currentOwners ?? [])
            );
        }

        return array_unique(array_values($finalOwners));
    }

    public function listReviewers(): array
    {
        $list = [];
        foreach ($this->compileReviewers() as $reviewers) {
            if (is_array($reviewers)) {
                foreach ($reviewers as $reviewer) {
                    $list[$reviewer] = $reviewer;
                }
                continue;
            }
            $list[$reviewers] = $reviewers;
        }
        return $list;
    }

    private function compileReviewers(): array
    {
        $groups = $this->config['reviewers'];

        $compiledReviewers = [];
        foreach ($groups as $group => $reviewers) {
            if (array_key_exists($group, $compiledReviewers)) {
                continue;
            }
            if (is_array($reviewers)) {
                foreach ($reviewers as $reviewer) {
                    $compiledReviewers[$group][$reviewer] = $reviewer;
                    $compiledReviewers[$reviewer] = $reviewer;
                }
            }
            if (is_string($reviewers)) {
                $compiledReviewers[$group][$reviewers] = $reviewers;
                $compiledReviewers[$reviewers] = $reviewers;
            }
        }

        return $compiledReviewers;
    }

    public function generateCodeowners(): string
    {
        $patterns = array_filter($this->getCompiledPatterns(), fn($owners) => $owners !== []);

        $codeowners = [];
        foreach ($patterns as $pattern => $owners) {
            $codeowners[$pattern] = sprintf(
                '%s %s',
                $pattern,
                implode(' ', array_map(fn($owner) => '@'.$owner, $owners))
            );
        }

        return implode(PHP_EOL, $codeowners);
    }

    private function getDefaultOwners(): array|string
    {
        return $this->compileReviewers()['defaults'];
    }
}
