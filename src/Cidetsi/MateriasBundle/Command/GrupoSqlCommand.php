<?php

namespace Cidetsi\MateriasBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// fetch the list of docentes and registry in the database
class GrupoSqlCommand extends ContainerAwareCommand
{
    protected $start_sequence = 500;

    protected function initialize(
            InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()
                         ->get('doctrine')->getEntityManager();
    }

    protected function configure() {
        parent::configure();

        $this->setName('cidetsi:sql:grupos')
             ->setDescription('Fetch courses information')
             ->addArgument(
                'params', InputArgument::OPTIONAL,
                'Connection parameters in .pgpass style')
              ->addArgument(
                'filename', InputArgument::OPTIONAL,
                'Filename in the register the sql content');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Procesando la información de materias ... ');
        $filename = $input->getArgument('filename');
        $params = $input->getArgument('params');

        $gestion = $this->getGestion();
        $materias = $this->getMaterias();

        if (!empty($filename) && !empty($params)) {
            list($host, $port, $database, $user, $pass) = explode(':', $params);

            $output->writeln('Conectando a la base de datos ... ' . $database);
            $dbconn = pg_connect(
                "host=$host dbname=$database user=$user password=$pass") or
                die('No se puede conectar a la base de datos');

            $output->writeln('Extrayendo la información de los materias ... ');
            $query = 'SELECT codigo, sigla, grupo FROM materia_dicta ORDER BY sigla';
            $result = pg_query($query);

            $array = array();
            while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
                $array[] = array(
                    'code' => $line['sigla'],
                    'grupo' => $line['grupo'],
                    'pg_id' => $line['codigo'],
                );
            }

            pg_free_result($result);
            pg_close($dbconn);

            $sql = 'INSERT INTO `grupo` (`ident`,`departamento`,`materia`,'
                    . '`gestion`,`name`,`pg_id`)' . PHP_EOL
                    . 'VALUES' . PHP_EOL;

            $values = array();
            $start = $this->start_sequence;
            foreach ($array as $item) {
                if (isset($materias[$item['code']])) {
                    $departamento =  $materias[$item['code']]['departamento'];
                    $materia = $materias[$item['code']]['materia'];

                    $values[] = $start++ . ',' . intval($departamento) . ','
                        . intval($materia) . ',' . $gestion->getIdent() . ',\''
                        . $item['grupo'] . '\',\'' . $item['pg_id'] . '\'';
                }
            }

            $sql .= '(' . implode("),\n(", $values) . ');' . PHP_EOL;
            $output->writeln('registrando salida en: ' . $filename);
            file_put_contents($filename, $sql);
        } else {
            // TODO
            $output->writeln('Generando lista ... ');
            $output->writeln('');
//
//            foreach ($materias as $code => $materia) {
//                $output->writeln($code . '  ' . $materia['name']);
//            }
        }
    }

    public function getGestion() {
        $query = $this->em->createQuery(
                'SELECT g FROM \Cidetsi\GestionesBundle\Entity\Gestion g '
                    . 'WHERE g.status = \'active\'');

        return $query->getSingleResult();
    }

    public function getMaterias() {
        $query = $this->em->createQuery(
                'SELECT m FROM \Cidetsi\MateriasBundle\Entity\Materia m');
        $materias = $query->getResult();

        $result = array();
        foreach ($materias as $materia) {
            $result[$materia->getCode()] = array(
                'departamento' => $materia->getDepartamento()->getIdent(),
                'materia' => $materia->getIdent(),
            );
        }

        return $result;
    }
}
