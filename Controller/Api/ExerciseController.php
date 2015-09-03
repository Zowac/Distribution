<?php

namespace UJM\ExoBundle\Controller\Api;

use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use UJM\ExoBundle\Entity\Exercise;
use UJM\ExoBundle\Manager\ApiManager;

/**
 * @EXT\Route(requirements={"id"="\d+"}, options={"expose"=true})
 * @EXT\Method("GET")
 */
class ExerciseController
{
    private $manager;

    /**
     * @DI\InjectParams({
     *     "manager" = @DI\Inject("ujm.exo.api_manager")
     * })
     *
     * @param ApiManager $manager
     */
    public function __construct(ApiManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @EXT\Route("/exercises/{id}")
     */
    public function exerciseAction(Exercise $exercise)
    {
        return new JsonResponse($this->manager->exportExercise($exercise));
    }
}
