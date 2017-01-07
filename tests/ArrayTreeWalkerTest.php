<?php

use PHPUnit\Framework\TestCase;
use RGilyov\ArrayTreeWalker;

class ArrayTreeWalkerTest extends TestCase
{
    /**
     * @var array
     */
    protected $tree = [
        'name'    => 'child',
        'parents' => [
            'mother' => [
                'name'    => 'mother',
                'parents' => [
                    'mother' => [
                        'name' => 'first grand mother'
                    ],
                    'father' => [
                        'name' => 'first grand father'
                    ]
                ]
            ],
            'father' => [
                'name'    => 'father',
                'parents' => [
                    'mother' => [
                        'name' => 'second grand mother'
                    ],
                    'father' => [
                        'name'    => 'second grand father',
                        'parents' => [
                            'mother' => [
                                'name' => 'second grand grand mother'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    /** @test */
    public function treeWalk()
    {
        $walker = new ArrayTreeWalker($this->tree);

        $this->assertEquals('first grand father', $walker->parents->mother->parents->father->get('name'));
        $this->assertEquals('first grand father', $walker->parents->mother->parents->father['name']);
        $this->assertEquals('first grand father', $walker->parents->mother->parents->father->name->get('name'));
        $this->assertEquals('first grand father', $walker->parents->mother->parents->father->name['name']);
        $this->assertEquals(null, $walker->parents->mother->parents->father->name->father->get('name'));
        $this->assertEquals(null, $walker->parents->mother->parents->father->name->father['name']);
    }

    /** @test */
    public function treeWalkWithNodeName()
    {
        $walker = new ArrayTreeWalker($this->tree, 'parents');

        $this->assertEquals('first grand father', $walker->mother->father->get('name'));
        $this->assertEquals('first grand father', $walker->mother->father['name']);
        $this->assertEquals('second grand grand mother', $walker->father->father->mother->get('name'));
        $this->assertEquals('second grand grand mother', $walker->father->father->mother['name']);
        $this->assertEquals('first grand father', $walker->mother->father->name->get('name'));
        $this->assertEquals('first grand father', $walker->mother->father->name['name']);
        $this->assertEquals(null, $walker->mother->father->name->father->get('name'));
        $this->assertEquals(null, $walker->mother->father->name->father['name']);
        $this->assertEquals(null, $walker->mother->father->father->get('name'));
        $this->assertEquals(null, $walker->mother->father->father['name']);
    }

    /** @test */
    public function methods()
    {
        $walker = new ArrayTreeWalker($this->tree, 'parents');

        $this->assertTrue(is_array($walker->toArray()));
        $this->assertEquals(2, $walker->count());
    }
}