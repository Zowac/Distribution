<?php

namespace UJM\ExoBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ResponseRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ResponseRepository extends EntityRepository
{
    /**
     * Allow to know if exists already a response for a question of a user's paper.
     *
     * @param int $paperID
     * @param int $questionID
     *
     * Return array[Response]
     */
    public function getAlreadyResponded($paperID, $questionID)
    {
        $qb = $this->createQueryBuilder('r');
        $qb->join('r.paper', 'p')
            ->join('r.question', 'q')
            ->where($qb->expr()->in('p.id', $paperID))
            ->andWhere($qb->expr()->in('q.id', $questionID));

        return $qb->getQuery()->getResult();
    }

    /**
     * Scores of an exercise for each paper.
     *
     *
     * @param int    $exoId id Exercise
     * @param String $order to order result
     *
     * Return array
     */
    public function getExerciseMarks($exoId, $order)
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('sum(r.mark) as noteExo, p.id as paper')
           ->join('r.paper', 'p')
           ->join('p.exercise', 'e')
           ->where('e.id = ?1')
           ->andWhere('p.interupt =  ?2')
           ->groupBy('p.id')
           ->orderBy($order, 'ASC')
           ->setParameters(array(1 => $exoId, 2 => 0));

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the reponses for a paper and an user.
     *
     *
     * @param int $paperID id paper
     *
     * Return array[Response]
     */
    public function getPaperResponses($paperID)
    {
        $qb = $this->createQueryBuilder('r');
        $qb->join('r.paper', 'p')
           ->leftJoin('p.user', 'u')
           ->where($qb->expr()->in('p.id', $paperID));

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the score for an exercise and an interaction with count.
     *
     * @param int $exoId
     * @param int $questionId
     *
     * Return array[Response]
     */
    public function getExerciseInterResponsesWithCount($exoId, $questionId)
    {
        $dql = '
            SELECT r.mark, count(r.mark) as nb
            FROM UJM\ExoBundle\Entity\Response r, UJM\ExoBundle\Entity\Question q, UJM\ExoBundle\Entity\Paper p
            WHERE r.question=q.id
            AND r.paper=p.id
            AND p.exercise= ?1
            AND r.question = ?2
            AND r.response != \'\'
            GROUP BY r.mark
        ';

        $query = $this->_em->createQuery($dql)
                      ->setParameters(array(1 => $exoId, 2 => $questionId));

        return $query->getResult();
    }

    /**
     * Send the score for an exercise and an interaction.
     *
     * @param int $exoId
     * @param int $questionId
     *
     * Return array[Response]
     */
    public function getExerciseInterResponses($exoId, $questionId)
    {
        $dql = '
            SELECT r.mark
            FROM UJM\ExoBundle\Entity\Response r, UJM\ExoBundle\Entity\Question q, UJM\ExoBundle\Entity\Paper p
            WHERE r.question=q.id
            AND r.paper=p.id
            AND p.exercise= ?1
            AND r.question = ?2
            ORDER BY p.id
        ';

        $query = $this->_em->createQuery($dql)
                      ->setParameters(array(1 => $exoId, 2 => $questionId));

        return $query->getResult();
    }

    /**
     * Get the score total for a paper.
     *
     *
     * @param int $paperID id paper
     *
     * Return int
     */
    public function getScoreExercise($paperId)
    {
        //doesn't take long open question not marked
        $dql = '
            SELECT sum(r.mark) as score
            FROM UJM\ExoBundle\Entity\Response r
            WHERE r.paper= ?1
            AND r.mark >= 0
        ';

        $query = $this->_em->createQuery($dql)
                      ->setParameters(array(1 => $paperId));

        $res = $query->getOneOrNullResult();

        return $res['score'];
    }
}
