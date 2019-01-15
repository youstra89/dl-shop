<?php

namespace App\Repository;

use App\Entity\AchatProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AchatProduit|null find($id, $lockMode = null, $lockVersion = null)
 * @method AchatProduit|null findOneBy(array $criteria, array $orderBy = null)
 * @method AchatProduit[]    findAll()
 * @method AchatProduit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AchatProduitRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AchatProduit::class);
    }

    // /**
    //  * @return AchatProduit[] Returns an array of AchatProduit objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AchatProduit
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
