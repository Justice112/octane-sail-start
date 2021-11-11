<?php

namespace App\Console\Commands\Modular;

use Nwidart\Modules\Generators\ModuleGenerator;
use Nwidart\Modules\Support\Config\GenerateConfigReader;

class ModularGenerator extends ModuleGenerator
{
    /**
     * Generate some resources.
     */
    public function generateResources()
    {
         parent::generateResources();

        if (GenerateConfigReader::read('repository')->generate() === true) {
            $options = $this->type=='api'?['--api'=>true]:[];
            $this->console->call('modular:make-repository', [
                'name' => $this->getName() . 'Repository',
                'module' => $this->getName(),
            ]+$options);
        }

        if (GenerateConfigReader::read('service')->generate() === true) {
            $options = $this->type=='api'?['--api'=>true]:[];
            $this->console->call('modular:make-service', [
                'name' => $this->getName() . 'Service',
                'module' => $this->getName(),
                '--model' => $this->getName(),
            ]+$options);
        }

        if (GenerateConfigReader::read('service')->generate() === true) {
            $options = $this->type=='api'?['--api'=>true]:[];
            $this->console->call('modular:make-api', [
                'name' => $this->getName() . 'Controller',
                'module' => $this->getName(),
                '--service' => $this->getName(). 'Service',
            ]+$options);
        }
    }
}
