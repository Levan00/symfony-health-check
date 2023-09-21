<?php

declare(strict_types=1);

namespace SymfonyHealthCheckBundle\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use SymfonyHealthCheckBundle\Check\CheckInterface;
use SymfonyHealthCheckBundle\Dto\HealthCheckDto;
use SymfonyHealthCheckBundle\Enum\Status;

final class HealthController extends AbstractController
{
    /**
     * @var array<CheckInterface>
     */
    private array $healthChecks = [];

    public function addHealthCheck(CheckInterface $healthCheck): void
    {
        $this->healthChecks[] = $healthCheck;
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function healthCheckAction(): JsonResponse
    {
        $result = [
            'status' => Status::PASS,
            'version' => 'version from git',
            'duration' => 0,
            'time' => (new DateTime())->format('Y-m-d H:i:s'),
            'checks' => [],
        ];

        $stopwatch = new Stopwatch();
        $stopwatch->start('health_check');

        foreach ($this->healthChecks as $healthCheck) {
            $response = $healthCheck->check();

            $result['checks'][] += $response->toArray();

            $result['status'] = match (true) {
                $response->getStatus() === Status::FAIL => Status::FAIL,
                $response->getStatus() === Status::WARNING && $result['status'] === Status::PASS => Status::WARNING,
            };
        }

        $result['duration'] = $stopwatch->stop('health_check');

        return new JsonResponse($result, Response::HTTP_OK);
    }
}