#!/usr/bin/env php
<?php
/**
 * @package    JDevAndLearn
 * @subpackage Base
 * @author     Nikolai Plath {@link https://github.com/elkuku}
 * @author     Created on 16-Jun-2012
 * @license    GNU/GPL
 */

// We are a valid Joomla!entry point.
define('_JEXEC', 1);

require dirname(__DIR__).'/bootstrap.php';

const ERR_TEST = 66;

const ERR_REQ = 2;

const ERR_DOMAIN = 10;

/**
 * JDL repository status class.
 *
 * @package  JdlUpdateRepos
 */
class JdlRepoStatus extends JdlApplicationCli
{
    private $repoBase = '';

    private $gitPath = '';

    /**
     * Execute the application.
     *
     * @throws Exception
     * @throws UnexpectedValueException
     * @throws DomainException
     *
     * @return void
     */
    public function doExecute()
    {
        $this->setup();

        $this->outputTitle(jgettext('Repository Status'))
            ->output(sprintf(jgettext('Repository Path: %s'), $this->repoBase));

        $folders = JFolder::folders($this->repoBase);

        $excludes = array();

        foreach($this->configXml->updates->statusExcludes->exclude as $exclude)
        {
            $excludes[] = (string)$exclude;
        }

        foreach($folders as $folder)
        {
            //-- Check if it is a git repo
            if(false == JFolder::exists($this->repoBase.'/'.$folder.'/.git'))
                continue;

            if(in_array($folder, $excludes))
                continue;

            $this->output()
                ->output($folder, true, '', '', 'bold');

            $cmd = 'cd "'.$this->repoBase.'/'.$folder.'" && git status -sb 2>&1';

            passthru($cmd, $ret);

            if(0 !== $ret)
                throw new DomainException(jgettext('Something went wrong pulling the repo'), ERR_DOMAIN);
        }

        $this->output()
            ->output(sprintf(jgettext('Execution time: %s secs.')
            , time() - $this->get('execution.timestamp')));

        $this->output()
            ->outputTitle(jgettext('Finished =;)'), 'green');

        if(1)
        {
            $this->output()
                ->output(jgettext('You may close this window now.'), true, 'red', '', 'bold');
        }
    }

    private function setup()
    {
        $this->repoBase = $this->configXml->global->repoDir;

        if(! $this->repoBase || ! JFolder::exists($this->repoBase))
            throw new DomainException(jgettext('Invalid repository base'), ERR_DOMAIN);

        if('' == $this->gitPath)
        {
            exec('which git 2>/dev/null', $output, $ret);

            if(0 !== $ret)
                throw new UnexpectedValueException(jgettext('Git must be installed to run this script'), ERR_REQ);

            $this->gitPath = 'git'; //$output
        }

        return $this;
    }
}

//-- Main routine

try
{
    $application = JApplicationCli::getInstance('JdlRepoStatus');

    JFactory::$application = $application;

    $application->execute();
}
catch(Exception $e)
{
    if(defined(COLORS) && COLORS)
    {
        $color = new Console_Color2;

        echo $color->color('red', null, 'grey')
            .' Error: '.$e->getMessage().' '
            .$color->color('reset')
            .NL;
    }
    else
    {
        echo 'ERROR: '.$e->getMessage().NL;
    }

    echo NL.$e->getTraceAsString().NL;

    exit($e->getCode() ? : 1);
}
