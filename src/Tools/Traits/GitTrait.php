<?php

namespace BimRunner\Tools\Traits;


trait GitTrait {
    use OSTrait;

    /**
     * Clone the repo.
     *
     * @param $gitRepo
     * @param $projectName
     * @param null $tagName
     */
    public function cloneGitRepo($gitRepo, $projectName, $tagName = NULL) {
        $dirname = FileHelper::me()->getExecutionDir() . $projectName;

        if (file_exists($dirname)) {
            if (!IOHelper::me()
              ->confirm('Le répertoire ' . $dirname . ' existe déjà. Voulez-vous l\'écraser? ')) {
                return;
            }
            else {
                $this->command('rm -rf ' . $dirname);
            }
        }

        // Clone du repo.
        $command = $this->s('git clone @tag @repo @project_name', [
          '@project_name' => $projectName,
          '@repo'         => $gitRepo,
          '@tag'          => $tagName ? ' --branch ' . $tagName : '',
        ]);

        IOHelper::me()->info($command);

        $this->command($command);
    }

    /**
     * Get repo tags.
     *
     * @param $repo
     *
     * @return array
     */
    public function getRepoTags($repo) {
        $output = $this->command('git ls-remote --tags ' . $repo);

        return array_map(function ($item) {
            $data = explode('tags/', $item)[1];

            return explode('^', $data)[0];
        }, $output);
    }

    /**
     * Retourne la liste des branches du repo.
     *
     * @param $repo
     *
     * @return array|string[]
     */
    public function getRepoBranchs($repo) {
        $output = $this->command('git ls-remote --heads ' . $repo);

        return array_map(function ($item) {
            $data = explode('heads/', $item)[1];

            return explode('^', $data)[0];
        }, $output);
    }

}
