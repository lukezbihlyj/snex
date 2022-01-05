<?php

namespace Snex\Asset\Console;

use Snex\Application;
use Snex\Asset\AssetFactory;
use Snex\Asset\Twig\AssetFormulaLoader;
use Snex\Console\Console;
use Snex\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Assetic\AssetWriter;
use Assetic\Factory\LazyAssetManager;
use Assetic\Extension\Twig\TwigResource;
use Assetic\Util\VarUtils;

class AssetDumpCommand extends ConsoleCommand
{
    protected AssetFactory $factory;

    public function __construct(Application $app, Console $console)
    {
        parent::__construct($app, $console);

        $this->factory = $app->services()->get('Snex\Asset\AssetFactory');
    }

    protected function configure() : void
    {
        $this->setName('asset:dump')
            ->setDescription('Dumps all configured assets into the target folder')
            ->setHelp('Uses the built-in asset management system to compile and minify assets. Pass the optional --ignore-folders flag to skip syncronizing folders to the target directory.')
            ->addOption('ignore-folders', null, InputOption::VALUE_NONE, 'Whether we should skip copying across entire folders');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $factory = $this->app->services()->get('Snex\Asset\AssetFactory');
        $assetManager = new LazyAssetManager($factory);

        // Step 1: Discover assets and add them to the asset manager
        // TODO: Don't hardcode this here
        $enabledEngines = $this->app->config()->get('render.enabled_engines', ['twig']);

        foreach ($enabledEngines as $engine) {
            if ($engine === 'twig') {
                $twigEngine = $this->app->services()->get('Snex\Render\Engine\TwigRenderEngine');

                $assetManager->setLoader('twig', new AssetFormulaLoader($twigEngine->getEnvironment()));

                $twigPath = $this->getAbsolutePath($this->app->config()->get('render.template_path'));
                $templates = $this->getAllFilesInDirectory($twigPath, false, '/^.+\.twig$/i');

                foreach ($templates as $templatePath) {
                    $templatePath = str_replace(rtrim($twigPath, '/') . '/', null, $templatePath);
                    $resource = new TwigResource($twigEngine->getLoader(), $templatePath);

                    $assetManager->addResource($resource, 'twig');
                }
            }
        }

        // Step 2: Write folders across first
        if ($input->getOption('ignore-folders')) {
            $output->writeln('<comment>' . date('H:i:s') . '</comment> <info>Skipping folders...</info>');
        } else {
            foreach ($this->app->config()->get('asset.folders') as $folder) {
                $source = $folder['source'];
                $target = $folder['target'];

                if (!is_dir($target)) {
                    $output->writeln('<comment>' . date('H:i:s') . '</comment> <info>[dir+]</info> ' . $this->getAbsolutePath($target));

                    if (@mkdir($target, 0777, true) === false) {
                        throw new Exception\AssetCreationException('Unable to create directory ' . $target);
                    }
                }

                $source = $this->getAbsolutePath($source);
                $sources = $this->getAllFilesInDirectory($source, true);

                foreach ($sources as $sourcePath) {
                    $relativeSourcePath = str_replace(rtrim($source, '/') . '/', null, $sourcePath);
                    $targetPath = $target . DIRECTORY_SEPARATOR . $relativeSourcePath;

                    if (is_dir($sourcePath)) {
                        if (is_dir($targetPath)) {
                            continue;
                        }

                        $output->writeln('<comment>' . date('H:i:s') . '</comment> <info>[dir+]</info> ' . $this->getAbsolutePath($targetPath));

                        if (@mkdir($targetPath, 0777, true) === false) {
                            throw new Exception\AssetCreationException('Unable to create directory ' . $targetPath);
                        }
                    } else {
                        if (is_file($targetPath) && md5_file($targetPath) == md5_file($sourcePath)) {
                            continue;
                        }

                        $output->writeln('<comment>' . date('H:i:s') . '</comment> <info>[file+]</info> ' . $this->getAbsolutePath($targetPath));

                        if (@file_put_contents($targetPath, file_get_contents($sourcePath)) === false) {
                            throw new Exception\AssetCreationException('Unable to write file ' . $targetPath);
                        }
                    }
                }
            }
        }

        // Step 3: Write our assets to the target path
        $writer = new AssetWriter($this->app->config()->get('asset.target_path'));

        foreach ($assetManager->getNames() as $name) {
            $asset = $assetManager->get($name);

            foreach (VarUtils::getCombinations($asset->getVars(), []) as $combination) {
                $asset->setValues($combination);

                $path = rtrim($this->app->config()->get('asset.target_path'), '/') . '/' . VarUtils::resolve(
                    $asset->getTargetPath(),
                    $asset->getVars(),
                    $asset->getValues()
                );

                if (!is_dir($dir = dirname($path))) {
                    $output->writeln('<comment>' . date('H:i:s') . '</comment> <info>[dir+]</info> ' . $this->getAbsolutePath($dir));

                    if (@mkdir($dir, 0777, true) === false) {
                        throw new Exception\AssetCreationException('Unable to create directory ' . $dir);
                    }
                }

                $output->writeln('<comment>' . date('H:i:s') . '</comment> <info>[file+]</info> ' . $this->getAbsolutePath($path));

                if (@file_put_contents($path, $asset->dump()) === false) {
                    throw new Exception\AssetCreationException('Unable to write file ' . $path);
                }
            }
        }
    }

    protected function getAbsolutePath(string $path) : string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];

        foreach ($parts as $part) {
            if ($part == '.') {
                continue;
            }

            if ($part == '..') {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        if ($path[0] == DIRECTORY_SEPARATOR) {
            array_unshift($absolutes, null);
        }

        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    protected function getAllFilesInDirectory(string $path, bool $includeDirectories = false, string $filter = null) : array
    {
        $files = scandir($path);
        $results = [];

        foreach ($files as $key => $value) {
            $subPath = $this->getAbsolutePath($path . DIRECTORY_SEPARATOR . $value);

            if ($value === '.' || $value === '..') {
                continue;
            }

            if (!is_dir($subPath)) {
                if (!$filter || preg_match($filter, $value)) {
                    $results[] = $subPath;
                }
            } else {
                if ($includeDirectories) {
                    if (!$filter || preg_match($filter, $value)) {
                        $results[] = $subPath;
                    }
                }

                $results = array_merge($results, $this->getAllFilesInDirectory($subPath, $includeDirectories, $filter));
            }
        }

        return array_unique($results);
    }
}
