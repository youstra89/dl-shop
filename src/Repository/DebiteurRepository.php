<?php

namespace App\Repository;

use App\Entity\Debiteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Debiteur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Debiteur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Debiteur[]    findAll()
 * @method Debiteur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DebiteurRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Debiteur::class);
    }

    // /**
    //  * @return Debiteur[] Returns an array of Debiteur objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Debiteur
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
