<?php

namespace App\Console\Commands\Modular;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Commands\GeneratorCommand;
use Nwidart\Modules\Exceptions\FileAlreadyExistException;
use Nwidart\Modules\Generators\FileGenerator;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateServiceCommand extends GeneratorCommand
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
    protected $name = 'modular:make-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new Service for the specified module.';

    public function getDefaultNamespace(): string
    {
        /** @var \Nwidart\Modules\Laravel\LaravelFileRepository $laravelFileRepository */
        $laravelFileRepository = $this->laravel['modules'];

        return $laravelFileRepository->config('paths.generator.service.path', 'Services');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the service.'],
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
            ['model', null, InputOption::VALUE_OPTIONAL, 'The model that should be assigned.', null],
        ];
    }

    /**
     * @param $path
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    private function bindingsHandle($path)
    {
        $contents = $this->getBindingsTemplateContents();

        try {
            $contents = str_replace('//', $contents, File::get($path));
            File::replace($path, $contents);

            $this->info("Update : {$path}");
        } catch (FileNotFoundException $e) {
            $this->error("File : {$path} not found.");
        }
    }

    /**
     * Get bindings template contents.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     *
     * @return string
     */
    protected function getBindingsTemplateContents()
    {
        /** @var \Nwidart\Modules\Laravel\LaravelFileRepository $laravelFileRepository */
        $laravelFileRepository = $this->laravel['modules'];
        $module = $laravelFileRepository->findOrFail($this->getModuleName());

        return (new Stub('/bindings.stub', [
            'NAMESPACE'       => $this->getClassNamespace($module),
            'NAMESPACE_IMPL'       => $this->getImplClassNamespace($module),
            'INTERFACE_CLASS' => $this->getClass(),
            'IMPLEMENT_CLASS' => $this->getClass() . 'Impl',
            'PLACEHOLDER'     => '//',
        ]))->render();
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

        return (new Stub('/service/implement.stub', [
            'NAMESPACE' => $this->getImplClassNamespace($module),
            'NAMESPACE_INTERFACE'       => $this->getClassNamespace($module),
            'CLASS'     => $this->getClass(),
            'MODEL' => $this->getModelName(),
            'MODELS' => Str::plural($this->getModelName()),
        ]))->render();
    }

    /**
     * Get interface template contents.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     *
     * @return string
     */
    protected function getInterfaceTemplateContents()
    {
        /** @var \Nwidart\Modules\Laravel\LaravelFileRepository $laravelFileRepository */
        $laravelFileRepository = $this->laravel['modules'];
        $module = $laravelFileRepository->findOrFail($this->getModuleName());

        return (new Stub('/service/interface.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getClass(),
            'MODEL' => $this->getModelName(),
            'MODELS' => Str::plural($this->getModelName()),
        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath($config = 'service')
    {
        /** @var \Nwidart\Modules\Laravel\LaravelFileRepository $laravelFileRepository */
        $laravelFileRepository = $this->laravel['modules'];
        $path = $laravelFileRepository->getModulePath($this->getModuleName());
        $apiPath = GenerateConfigReader::read($config);

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
        $interfacePath = str_replace('\\', '/', $this->getDestinationFilePath());
        $this->interfaceHandle($interfacePath);

        $implPath = Str::before(str_replace('\\', '/', $this->getDestinationFilePath('service-impl')), '.php') . 'Impl.php';
        $this->implementationHandle($implPath);

        $path = module_path($this->getModuleName()) . '/Providers/ServiceServiceProvider.php';
        $this->bindingsHandle($path);

        return 0;
    }

    /**
     * Execute the console interface command.
     *
     * @param $path
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    protected function interfaceHandle($path)
    {
        /** @var \Illuminate\Filesystem\Filesystem $filesystem */
        $filesystem = $this->laravel['files'];
        if (!$filesystem->isDirectory($dir = dirname($path))) {
            $filesystem->makeDirectory($dir, 0777, true);
        }

        $contents = $this->getInterfaceTemplateContents();

        try {
            with(new FileGenerator($path, $contents))->generate();

            $this->info("Created : {$path}");
        } catch (FileAlreadyExistException $e) {
            $this->error("File : {$path} already exists.");
        }
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
    private function getModelName()
    {
        return (string) $this->option('model')
            ?: Str::before(class_basename((string) $this->argument($this->argumentName)), 'Repository');
    }

    private function getImplClassNamespace($module)
    {
        return $this->getClassNamespace($module) . '\Impl';
    }
}
