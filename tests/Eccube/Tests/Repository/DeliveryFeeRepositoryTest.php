<?php

namespace Eccube\Tests\Repository;

use Eccube\Entity\Master\Pref;
use Eccube\Tests\EccubeTestCase;


/**
 * DeliveryFeeRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class DeliveryFeeRepositoryTest extends EccubeTestCase
{
    public function setUp()
    {
        $this->markTestIncomplete(get_class($this).' は未実装です');
        parent::setUp();
    }

    public function testFindOrCreateWithFind()
    {
        $Delivery = $this->app['eccube.repository.delivery']->find(1);
        $Pref = $this->app['eccube.repository.master.pref']->find(1);

        $this->assertNotNull($Pref);
        $this->assertNotNull($Delivery);

        $DeliveryFee = $this->app['eccube.repository.delivery_fee']->findOrCreate(
            array('Delivery' => $Delivery, 'Pref' => $Pref)
        );

        $this->expected = 1000; // 配送料の初期設定
        $this->actual = $DeliveryFee->getFee();
        $this->verify('配送料は'.$this->expected.'ではありません');
    }

    public function testFindOrCreateWithCreate()
    {
        $Delivery = $this->app['eccube.repository.delivery']->find(1);
        $Pref = new Pref();

        $Pref
            ->setId(500)
            ->setName('その他')
            ->setSortNo(99);
        $this->app['orm.em']->persist($Pref);
        $this->app['orm.em']->flush();

        $DeliveryFee = $this->app['eccube.repository.delivery_fee']->findOrCreate(
            array('Delivery' => $Delivery, 'Pref' => $Pref)
        );

        $this->expected = 0;
        $this->actual = $DeliveryFee->getFee();

        $this->verify('配送料は'.$this->expected.'ではありません');
    }
}
