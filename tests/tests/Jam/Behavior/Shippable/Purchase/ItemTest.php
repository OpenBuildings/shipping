<?php

class Jam_Behavior_Shippable_Purchase_ItemTest extends \PHPUnit\Framework\TestCase {

    public function test_initialize()
    {
        $association = Jam::meta('purchase_item_product')
            ->association('shipping_item');

        $this->assertInstanceOf('Jam_Association_Hasone', $association);
        $this->assertAttributeSame('purchase_item_id', 'foreign_key', $association);
        $this->assertAttributeSame('purchase_item', 'inverse_of', $association);
        $this->assertAttributeSame(Jam_Association::DELETE, 'dependent', $association);
    }
}
