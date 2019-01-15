<?php

namespace App\Repository;

use App\Entity\Reglement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Reglement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reglement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reglement[]    findAll()
 * @method Reglement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReglementRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Reglement::class);
    }

    // /**
    //  * @return Reglement[] Returns an array of Reglement objects
    //  */
    public function reglementsIncomplets()
    {
        return $this->createQueryBuilder('r')
            ->select('c.id, SUM(r.montant) AS montant')
            ->join('r.commande', 'c')
            ->where('c.solde = FALSE')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult()
            ;
    }


    /*
    public function findOneBySomeField($value): ?Reglement
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
