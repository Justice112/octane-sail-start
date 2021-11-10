<?php

namespace App\Console\Commands\Modular;

use Illuminate\Support\Str;
use Nwidart\Modules\Commands\GeneratorCommand;
use Nwidart\Modules\Exceptions\FileAlreadyExistException;
use Nwidart\Modules\Generators\FileGenerator;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateApiCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'modular:make-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new Api for the specified module.';

    public function getDefaultNamespace(): string
    {
        /** @var \Nwidart\Modules\Laravel\LaravelFileRepository $laravelFileRepository */
        $laravelFileRepository = $this->laravel['modules'];

        return $laravelFileRepository->config('paths.generator.api.path', 'Http/Controllers/Api/V1');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the api.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['service', null, InputOption::VALUE_OPTIONAL, 'The model that should be assigned.', null],
        ];
    }


    /**
     * Get implementation template contents.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     *
     * @return string
     */
    protected function getImplementTemplateContents()
    {
        /** @var \Nwidart\Modules\Laravel\LaravelFileRepository $laravelFileRepository */
        $laravelFileRepository = $this->laravel['modules'];
        $module = $laravelFileRepository->findOrFail($this->getModuleName());

        $root_namespace = $laravelFileRepository->config('namespace');
        $root_namespace .= '\\' . $module->getStudlyName();
        $service_namespace = '\\' . $root_namespace . '\\Services\\' . $this->getServiceName();

        return (new Stub('/api/implement.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getClass(),
            'SERVICE'     => $service_namespace,
        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath()
    {
        /** @var \Nwidart\Modules\Laravel\LaravelFileRepository $laravelFileRepository */
        $laravelFileRepository = $this->laravel['modules'];
        $path = $laravelFileRepository->getModulePath($this->getModuleName());
        $apiPath = GenerateConfigReader::read('api');

        return $path . $apiPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly((string) $this->argument('name'));
    }

    /**
     * Execute the console command.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function handle(): int
    {
        $path = str_replace('\\', '/', $this->getDestinationFilePath());
        $this->implementationHandle($path);

        return 0;
    }


    /**
     * Execute the console implementation command.
     *
     * @param $path
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    protected function implementationHandle($path)
    {
        /** @var \Illuminate\Filesystem\Filesystem $filesystem */
        $filesystem = $this->laravel['files'];
        if (!$filesystem->isDirectory($dir = dirname($path))) {
            $filesystem->makeDirectory($dir, 0777, true);
        }

        $contents = $this->getImplementTemplateContents();

        try {
            with(new FileGenerator($path, $contents))->generate();

            $this->info("Created : {$path}");
        } catch (FileAlreadyExistException $e) {
            $this->error("File : {$path} already exists.");
        }
    }

    /**
     * Get template contents.
     *
     * @return string
     */
    protected function getTemplateContents()
    {
        return '';
    }


    /**
     * @return string
     */
    private function getServiceName()
    {
        return (string) $this->option('service')
            ?: Str::before(class_basename((string) $this->argument($this->argumentName)), 'Service');
    }
}
