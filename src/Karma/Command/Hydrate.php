<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Application;
use Karma\Command;
use Karma\Configuration\FilterInputVariable;

class Hydrate extends Command
{
    use FilterInputVariable;
    
    const
        ENV_DEV = 'dev',
        OPTION_ASSIGNMENT = '=';
    
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('hydrate')
            ->setDescription('Hydrate dist files')
            
            ->addArgument('sourcePath', InputArgument::REQUIRED, 'source path to hydrate')
            
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_DEV)
            ->addOption('suffix', null, InputOption::VALUE_REQUIRED, 'File suffix', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation mode')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Backup overwritten files')
            ->addOption('override', 'o', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Override variable values', array())
            ->addOption('data', 'd', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Custom data values', array())
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $suffix = $input->getOption('suffix');
        if($suffix === null)
        {
            $suffix = Application::DEFAULT_DISTFILE_SUFFIX;
            
            $profile = $this->app['profile'];
            if($profile->hasTemplatesSuffix())
            {
                $suffix = $profile->getTemplatesSuffix();
            }
        }
        
        $environment = $input->getOption('env'); 
        
        $this->output->writeln(sprintf(
            '<info>Hydrate <comment>%s</comment> with <comment>%s</comment> values</info>',
            $input->getArgument('sourcePath'),
            $environment
        ));
        
        $this->app['sources.path']     = $input->getArgument('sourcePath');
        $this->app['distFiles.suffix'] = $suffix;
        
        $this->overrideValues($input);
        $this->setCustomData($input);
        
        $hydrator = $this->app['hydrator'];
        
        if($input->getOption('dry-run'))
        {
            $this->output->writeln("<fg=cyan>*** Run in dry-run mode ***</fg=cyan>");
            $hydrator->setDryRun();
        }
        
        if($input->getOption('backup'))
        {
            $this->output->writeln("<fg=cyan>Backup enabled</fg=cyan>");
            $hydrator->enableBackup();
        }
            
        $hydrator->hydrate($environment);
    }
    
    private function overrideValues(InputInterface $input)
    {
        $overrideStrings = $input->getOption('override');

        if(! is_array($overrideStrings))
        {
            $overrideStrings = array($overrideStrings);
        }
        
        $overrides = array();
        foreach($overrideStrings as $overrideString)
        {
            if(stripos($overrideString, self::OPTION_ASSIGNMENT) === false)
            {
                throw new \InvalidArgumentException(sprintf(
                    'override option must contain %c : --override <variable>=<value>',
                    self::OPTION_ASSIGNMENT                        
                ));    
            }

            list($variable, $value) = explode(self::OPTION_ASSIGNMENT, $overrideString, 2);
            
            if(array_key_exists($variable, $overrides))
            {
                throw new \InvalidArgumentException('Duplicated override variable ' . $variable);    
            }
            
            $overrides[$variable] = $value;
        }
        
        $this->processOverridenVariables($overrides);
    }
    
    private function setCustomData(InputInterface $input)
    {
        $dataStrings = $input->getOption('data');

        if(! is_array($dataStrings))
        {
            $dataStrings = array($dataStrings);
        }
        
        $data = array();
        foreach($dataStrings as $dataString)
        {
            if(stripos($dataString, self::OPTION_ASSIGNMENT) === false)
            {
                throw new \InvalidArgumentException(sprintf(
                    'custom data option must contain %c : --data <variable>=<value>',
                    self::OPTION_ASSIGNMENT                        
                ));    
            }

            list($variable, $value) = explode(self::OPTION_ASSIGNMENT, $dataString, 2);
            
            if(array_key_exists($variable, $data))
            {
                throw new \InvalidArgumentException('Duplicated override variable ' . $variable);    
            }
            
            $data[$variable] = $value;
        }
        
        $this->processCustomData($data);
    }
    
    private function processOverridenVariables(array $overrides)
    {
        $reader = $this->app['configuration'];

        foreach($overrides as $variable => $value)
        {
            $this->output->writeln(sprintf(
               'Set <option=bold>%s</option=bold> with value <option=bold>%s</option=bold>',
               $variable,
               $value
            ));
            
            $reader->overrideVariable($variable, $this->filterValue($value));
        }
    }
    
    private function processCustomData(array $data)
    {
        $reader = $this->app['configuration'];

        foreach($data as $variable => $value)
        {
            $this->output->writeln(sprintf(
               'Set custom data <option=bold>%s</option=bold> with value <option=bold>%s</option=bold>',
               $variable,
               $value
            ));
            
            $reader->setCustomData($variable, $this->filterValue($value));
        }
    }
}