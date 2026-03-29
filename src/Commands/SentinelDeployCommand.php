<?php

namespace UpgradeLabs\SentinelLaravel\Commands;

use Illuminate\Console\Command;
use UpgradeLabs\SentinelLaravel\SentinelClient;

class SentinelDeployCommand extends Command
{
    protected $signature = 'sentinel:deploy
        {--version= : Release version (e.g. 1.2.0)}
        {--commit= : Git commit hash}
        {--branch= : Git branch name}
        {--environment= : Environment (production, staging, etc.)}
        {--deployer= : Who or what triggered the deploy}
        {--description= : Optional deploy description}
        {--auto : Auto-detect version, commit, and branch from git}';

    protected $description = 'Notify Sentinel of a new deployment';

    public function handle(SentinelClient $client)
    {
        if (! $client->isConfigured()) {
            $this->error('Sentinel is not configured. Set SENTINEL_TOKEN in your .env file.');

            return 1;
        }

        $data = array_filter([
            'version' => $this->option('version'),
            'commit_hash' => $this->option('commit'),
            'branch' => $this->option('branch'),
            'environment' => $this->option('environment') ?: app()->environment(),
            'deployer' => $this->option('deployer'),
            'description' => $this->option('description'),
        ]);

        if ($this->option('auto')) {
            $data = array_merge($this->detectFromGit(), $data);
        }

        $response = $client->deploy($data);

        if ($response && $response->successful()) {
            $json = $response->json();
            $this->info('Deploy recorded for ' . ($json['project'] ?? 'project') . '.');

            if (isset($data['version'])) {
                $this->line('  Version: ' . $data['version']);
            }
            if (isset($data['commit_hash'])) {
                $this->line('  Commit:  ' . $data['commit_hash']);
            }
            if (isset($data['branch'])) {
                $this->line('  Branch:  ' . $data['branch']);
            }

            return 0;
        }

        $this->error('Failed to record deploy.');

        if ($response) {
            $this->line('Status: ' . $response->status());
            $this->line('Response: ' . $response->body());
        }

        return 1;
    }

    /**
     * @return array
     */
    private function detectFromGit()
    {
        $data = [];

        $commit = trim((string) @shell_exec('git rev-parse HEAD 2>/dev/null'));
        if ($commit && strlen($commit) === 40) {
            $data['commit_hash'] = $commit;
        }

        $branch = trim((string) @shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null'));
        if ($branch && $branch !== 'HEAD') {
            $data['branch'] = $branch;
        }

        $tag = trim((string) @shell_exec('git describe --tags --exact-match 2>/dev/null'));
        if ($tag) {
            $data['version'] = $tag;
        }

        return $data;
    }
}
