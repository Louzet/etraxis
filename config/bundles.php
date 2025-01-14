<?php

return [
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class                 => ['all' => true],
    Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle::class       => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class         => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class     => ['all' => true],
    League\Tactician\Bundle\TacticianBundle::class                       => ['all' => true],
    Nelmio\ApiDocBundle\NelmioApiDocBundle::class                        => ['dev' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class                => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class                    => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class                  => ['all' => true],
    Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class            => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class                          => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class            => ['dev' => true, 'test' => true],
    Symfony\Bundle\WebServerBundle\WebServerBundle::class                => ['dev' => true],
];
