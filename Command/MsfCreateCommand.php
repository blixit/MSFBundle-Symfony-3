<?php

namespace Blixit\MSFBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MsfCreateCommand extends ContainerAwareCommand
{
    private $output;

    private $name;

    private $namespace;

    private $configuration;

    const default_formtype_path = 'src/AppBundle/Form';
    const default_formtype_namespace = 'AppBundle\Form';

    protected function configure()
    {
        $this
            ->setName('msf:create')
            ->setDescription('Creates a new MSF form')
            ->addArgument('name',InputArgument::OPTIONAL, 'The fullname of the class to generate. Eg: AppBundle/Form/MSF/MSFRegistrationType')
            ->addOption('output', 'o',InputOption::VALUE_REQUIRED, 'The output path', self::default_formtype_path)
            ->addOption('default-formtype', 'f', InputOption::VALUE_OPTIONAL, 'Auto detects form types classes from their related entity',false)
            ->addOption('default-paths', 'p', InputOption::VALUE_OPTIONAL, 'Auto detects form types classes from their related entity',true)
            ->addOption('destroy-data', 'd', InputOption::VALUE_OPTIONAL, 'Delete data after the last MSF transition',true)
        ;
    }

    private function extractClassFromNamespace($namespace){
        $test = preg_replace("/[a-zA-Z0-9]+$/S",'',$namespace);
        return trim($test,"\\");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helperQuestion = $this->getHelper('question');

        //Output
        $root = $this->getContainer()->get('kernel')->getRootDir().'/..';
        $this->output = $root.'/'.$input->getOption('output');

        if(! is_dir($this->output)){
            throw new \Exception("Invalid output : '$this->output' is not a directory.");
        }

        //Name
        $this->name = $input->getArgument('name');
        if(empty($this->name)){

            $question = new Question('Enter the fullname of your MSF class [Eg: MSFRegistrationType ] : ');

            while ( empty($this->name)  ) {
                $this->name = $helperQuestion->ask($input, $output, $question);
            }
        }

        $output->writeln("** Setting configuration **");

        //set default-paths
        $this->configuration['__default_paths'] = $input->getOption('default-paths');

        //set root
        $question = new Question("Enter the default route name (Eg: homepage ) : ");
        while ( empty($this->configuration['__root'])  ) {
            $this->configuration['__root'] = $helperQuestion->ask($input, $output, $question);
        }

        //set final redirection
        $question = new Question("Enter the default final redirection [Default : '".$this->configuration['__root']."' ] : ");
        $this->configuration['__final_redirection'] = $helperQuestion->ask($input, $output, $question);
        if ( empty($this->configuration['__final_redirection'])  ) {
            $this->configuration['__final_redirection'] = $this->configuration['__root'];
        }

        //set cancel redirection
        $question = new Question("Enter the default redirection on cancel [Default : '".$this->configuration['__root']."' ] : ");
        $this->configuration['__cancel_redirection'] = $helperQuestion->ask($input, $output, $question);
        if ( empty($this->configuration['__cancel_redirection'])  ) {
            $this->configuration['__cancel_redirection'] = $this->configuration['__root'];
        }

        //set default formtype
        $this->configuration['__default_formType'] = boolval($input->getOption('default-formtype'));

        if(! $this->configuration['__default_formType'] ){
            $question = new Question("Enter the default namespace for searching FormType classes [Default : '".self::default_formtype_namespace."'] ");
            $this->configuration['__default_formType_path'] = $helperQuestion->ask($input, $output, $question);
            if(empty($this->configuration['__default_formType_path']))
                $this->configuration['__default_formType_path'] = self::default_formtype_namespace;
        }else{
            $this->configuration['__default_formType_path'] = self::default_formtype_namespace;
        }

        //set destroy data
        $this->configuration['destroy_data'] = $input->getOption('destroy-data');


        $output->writeln("** Setting states **");
        $this->configuration['states'] = [];

        $answer = 'default_answer';
        do{
            $askName = new Question("State name : ");
            $answer = $helperQuestion->ask($input, $output, $askName);
            if(!empty($answer)){
                $askEntity = new Question("Enter the fullname of the entity class (Eg: \AppBundle\Entity\User ) : ");
                while(empty($this->configuration['states'][$answer])){
                    //Entity
                    $entity = $helperQuestion->ask($input, $output, $askEntity);
                    try{
                        (new \ReflectionClass($entity));
                        $this->configuration['states'][$answer]['entity'] = $entity;
                        $output->writeln("Done !");
                    }catch (\Exception $e){
                        $output->writeln("Error : ".$e->getMessage());
                    }

                    //FormType
                    $askFormType = new Question("Enter the fullname of the formtype class (Eg: \AppBundle\Form\UserType ) : ");
                    if( $this->configuration['__default_formType']){

                        if($input->getOption('verbose')){
                            $output->writeln("Detecting form type for '".$entity."' ... ");
                        }

                        $tmp = explode("\\",$entity);
                        $className = end($tmp);

                        $formtype = $this->configuration['__default_formType_path'].'\\'.$className.'Type';
                        do{
                            $error = false;
                            try{
                                (new \ReflectionClass($formtype));
                                $this->configuration['states'][$answer]['className'] = $className;
                                $this->configuration['states'][$answer]['formtype'] = $formtype;
                                $output->writeln("Done !");
                            }catch (\Exception $e){
                                $error = true;
                                $output->writeln("Error : ".$e->getMessage());
                                $formtype = $helperQuestion->ask($input, $output, $askFormType);
                            }
                        }while(empty($formtype) || $error);

                    }else{
                        $formtype = $helperQuestion->ask($input, $output, $askFormType);

                        $tmp = explode("\\",$entity);
                        $className = end($tmp);

                        $this->configuration['states'][$answer]['className'] = $className;
                        $this->configuration['states'][$answer]['formtype'] = $formtype;
                    }

                    //Action before
                    $askBefore = new ChoiceQuestion(
                        "Add before action ? (Default 'null')",
                        array('null', 'state', 'function'),
                        '0'
                    );
                    $this->configuration['states'][$answer]['before'] = $helperQuestion->ask($input,$output,$askBefore);

                    //Action after
                    $askAfter = new ChoiceQuestion(
                        "Add after action ? (Default 'null')",
                        array('null', 'state', 'function'),
                        '0'
                    );
                    $this->configuration['states'][$answer]['after'] = $helperQuestion->ask($input,$output,$askAfter);

                    //Action cancel
                    $askCancel = new ChoiceQuestion(
                        "Add cancel action ? (Default 'null')",
                        array('null', 'state', 'function'),
                        '0'
                    );
                    $this->configuration['states'][$answer]['cancel'] = $helperQuestion->ask($input,$output,$askCancel);

                    //Action validation
                    $askValidation = new ChoiceQuestion(
                        "Add validation action ? (Default 'function')",
                        array('null', 'state', 'function'),
                        '2'
                    );
                    $this->configuration['states'][$answer]['validation'] = $helperQuestion->ask($input,$output,$askValidation);

                    //local redirection
                    $question = new Question("Enter the local redirection [Default : '".$this->configuration['__root']."' ] : ");
                    $redirection = $helperQuestion->ask($input, $output, $question);
                    if ( empty($redirection)  ) {
                        $this->configuration['states'][$answer]['redirection'] = $redirection;
                    }

                }

            }
        }while(!empty($answer));

        $output->writeln("** Setting buttons **");

        $askButton = new ConfirmationQuestion("Add submit button ? (Default 'true') ",true);
        $this->configuration['buttons']['submit'] = $helperQuestion->ask($input, $output, $askButton);
        $askButton = new ConfirmationQuestion("Add next button ? (Default 'true') ",true);
        $this->configuration['buttons']['next'] = $helperQuestion->ask($input, $output, $askButton);
        $askButton = new ConfirmationQuestion("Add previous button ? (Default 'true') ",true);
        $this->configuration['buttons']['previous'] = $helperQuestion->ask($input, $output, $askButton);
        $askButton = new ConfirmationQuestion("Add cancel button ? (Default 'true') ",true);
        $this->configuration['buttons']['cancel'] = $helperQuestion->ask($input, $output, $askButton);

        //Path to create
        $this->namespace = self::default_formtype_namespace;
        $output->writeln("The MSF '$this->name' will be written \n- in the directory '$this->output' \n- in the namespace '$this->namespace' ");

        $this->writeMSF();

        $output->writeln('Done !');
    }

    private function writeMSF(){
        $handle = fopen($this->output.'/'.$this->name.'.php',"w+");

        $documentation =
            "<?php\n".
            "/**\n".
            " * Generated by MSF Bundle.\n".
            " */\n";

        $writer = $documentation;

        $writer .= $this->writeDependencies();
        $writer .= $this->writeClass($handle);

        fwrite($handle, $writer);

        fclose($handle);
    }

    private function writeDependencies() {
        $writer = "namespace $this->namespace;\n\n";

        $writer .= "use Blixit\MSFBundle\Form\Builder\MSFBuilderInterface; \n";
        $writer .= "use Blixit\MSFBundle\Form\Type\MSFAbstractType; \n\n";

        foreach ($this->configuration['states'] as $name => $infos) {
            $writer .= "use ".($infos['entity']).";\n";
            $writer .= "use ".($infos['formtype']).";\n";
        }

        $writer .= "\n\n";

        return $writer;
    }

    private function writeClass(){

        $writer = $this->writeLine(0,"class $this->name extends MSFAbstractType {\n");

        $writer .= $this->writeMethodConfigure();
        $writer .= $this->writeMethodBuildMSF();

        $writer .= $this->writeLine(0,"}");

        return $writer;
    }

    private function writeMethodConfigure(){



        $writer = $this->writeLine(1,"public function configure(){");
        $writer .= $this->writeLine(2,'return [');
        $writer .= $this->writeLine(3,"'__default_paths'    =>  ".($this->configuration['__default_paths'] ? "true" : "false").",");
        $writer .= $this->writeLine(3,"'__root'    =>  '".$this->configuration['__root']."',");
        $writer .= $this->writeLine(3,"'__final_redirection'    =>  '".$this->configuration['__final_redirection']."',");
        $writer .= $this->writeLine(3,"'__on_cancel'    =>  ['redirection' => '".$this->configuration['__cancel_redirection']."' ],");
        $writer .= $this->writeLine(3,"'__on_terminate'    =>  ['destroy_data' => ".($this->configuration['destroy_data'] ? "true" : "false")." ],");
        $writer .= "\n";
        foreach ($this->configuration['states'] as $name => $infos) {
            $writer .= $this->writeAddState($name,$infos);
        }

        $writer .= $this->writeLine(2,'];');
        $writer .= $this->writeLine(1,"}\n");

        return $writer;
    }

    private function writeMethodBuildMSF(){

        $writer = $this->writeLine(1,"public function buildMSF(){");

        if($this->configuration['buttons']['submit'])
            $writer .= $this->writeAddButton('Submit','Valider');
        if($this->configuration['buttons']['next'])
            $writer .= $this->writeAddButton('Next','Suivant');
        if($this->configuration['buttons']['previous'])
            $writer .= $this->writeAddButton('Previous','Précédent');
        if($this->configuration['buttons']['cancel'])
            $writer .= $this->writeAddButton('Cancel','Annuler');

        $writer .= $this->writeLine(2,'return $this;');
        $writer .= $this->writeLine(1,"}\n");

        return $writer;
    }

    private function writeAddState($state, $infos){
        $writer = $this->writeLine(3, "'".$state."'    =>  [");
        foreach ($infos as $key =>  $value) {
            /*if($value == "null")
                $writer .= $this->writeLine(4,"'".$key."'   =>  null,");
            else*/
            if($key == "className")
                continue;

            if($key == "validation"){
                $writer .= $this->writeLine(4,"'".$key."'   =>  function(".'$msfData, $formData'."){");
                $writer .= $this->writeLine(5, "return true;");
                $writer .= $this->writeLine(4, "},");
            }elseif($key == "cancel"){
                if($value == "function"){
                    $writer .= $this->writeLine(4,"'".$key."'   =>  function(".'$msfData'."){");
                    $writer .= $this->writeLine(5, "return 'state';");
                    $writer .= $this->writeLine(4, "},");
                }elseif($value == "state"){
                    $writer .= $this->writeLine(4,"'".$key."'   =>  '".$value."',");
                }
            }elseif($key == "before"){
                if($value == "function"){
                    $writer .= $this->writeLine(4,"'".$key."'   =>  function(".'$msfData'."){");
                    $writer .= $this->writeLine(5, "return 'state';");
                    $writer .= $this->writeLine(4, "},");
                }elseif($value == "state"){
                    $writer .= $this->writeLine(4,"'".$key."'   =>  '".$value."',");
                }
            }elseif($key == "after"){
                if($value == "function"){
                    $writer .= $this->writeLine(4,"'".$key."'   =>  function(".'$msfData'."){");
                    $writer .= $this->writeLine(5, "return 'state';");
                    $writer .= $this->writeLine(4, "},");
                }elseif($value == "state"){
                    $writer .= $this->writeLine(4,"'".$key."'   =>  '".$value."',");
                }
            }elseif ($key == "entity"){
                $writer .= $this->writeLine(4,"'".$key."'   =>  ".$infos['className']."::class,");
            }elseif ($key == "formtype") {
                $writer .= $this->writeLine(4,"'".$key."'   =>  ".$infos['className']."Type::class,");
            }else{
                $writer .= $this->writeLine(4,"'".$key."'   =>  '".$value."',");
            }
        }
        $writer .= $this->writeLine(3, "]");

        return $writer;
    }

    private function writeAddButton($name,$label){
        $writer = $this->writeLine(2, '$this->add'.$name.'Button([');
        $writer .= $this->writeLine(3, "'label'=>'".$label."',");
        $writer .= $this->writeLine(3, "'attr'=>[");
        $writer .= $this->writeLine(4, "'class'=>''");
        $writer .= $this->writeLine(3, "]");
        $writer .= $this->writeLine(2, "]);");

        return $writer;
    }

    private function writeLine($n, $line, $newline = true){
        $tab = '';
        for ($i = 0; $i<$n; $i++){
            $tab .= "\t";
        }
        return $tab.$line.($newline ? "\n" : '');
    }

}
