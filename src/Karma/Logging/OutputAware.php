<?php

namespace Karma\Logging;

use Symfony\Component\Console\Output\OutputInterface;

trait OutputAware
{
    private
        $output = null;

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        
        return $this;
    }
    
    protected function debug($messages, $newline = false, $type = OutputInterface::OUTPUT_NORMAL)
    {
        return $this->write($messages, $newline, $type, OutputInterface::VERBOSITY_DEBUG, 'cyan');
    }
    
    private function write($messages, $newline, $type, $verbosity, $textColor)
    {
        if($this->output instanceof OutputInterface)
        {
            if($verbosity <= $this->output->getVerbosity())
            {
                if(! is_array($messages))
                {
                    $messages = array($messages);
                }
    
                array_walk($messages, function(& $message){
                    $message = "<fg=$textColor>$message</fg=$textColor>";
                });
    
                    $this->output->write($messages, $newline, $type);
            }
        }
    }
}