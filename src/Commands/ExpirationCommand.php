<?php

declare(strict_types=1);
/**
 * This file is part of he426100/tus-php-hyperf.
 *
 * @link     https://github.com/he426100/tus-php-hyperf
 * @contact  mrpzx001@gmail.com
 * @license  https://github.com/he426100/tus-php-hyperf/blob/master/LICENSE
 */
namespace Tus\Commands;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Context\ApplicationContext;
use Tus\Tus\Server as TusServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpirationCommand extends Command
{
    /** @var TusServer */
    protected $server;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('tus:expired')
            ->setDescription('Remove expired uploads.')
            ->setHelp('Deletes all expired uploads to free server resources. Values can be redis, file or apcu. Defaults to file.')
            ->addOption(
                'config',
                'c',
                InputArgument::OPTIONAL,
                'File to get config parameters from.'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '<info>Cleaning server resources</info>',
            '<info>=========================</info>',
            '',
        ]);

        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $options = $input->getOption('config');
        if (! empty($options)) {
            foreach ($options as $key => $value) {
                $config->set($key, $value);
            }
        }

        $this->server = ApplicationContext::getContainer()->get(TusServer::class);

        $deleted = $this->server->handleExpiration();

        if (empty($deleted)) {
            $output->writeln('<comment>Nothing to delete.</comment>');
        } else {
            foreach ($deleted as $key => $item) {
                $output->writeln('<comment>' . ($key + 1) . ". Deleted {$item['name']} from " . \dirname($item['file_path']) . '</comment>');
            }
        }

        $output->writeln('');

        return 0;
    }
}
