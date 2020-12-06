<?php

namespace AppBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Format;
use AppBundle\Entity\Deckslot;

/**
 * Decorator for a collection of SlotInterface 
 */
class SlotCollectionDecorator implements \AppBundle\Model\SlotCollectionInterface
{
	protected $slots;
	
	public function __construct(\Doctrine\Common\Collections\Collection $slots, Format $format = NULL)
	{
		$this->slots = $slots;
		$this->format = $format;
	}
	
	public function add($element)
	{
		return $this->slots->add($element);
	}

	public function removeElement($element)
	{
		return $this->slots->removeElement($element);
	}
	
	public function count($mode = null)
	{
		return $this->slots->count($mode);
	}
	
	public function getIterator()
	{
		return $this->slots->getIterator();
	}
	
	public function offsetExists($offset)
	{
		return $this->slots->offsetExists($offset);
	}
	
	public function offsetGet($offset)
	{
		return $this->slots->offsetGet($offset);
	}
	
	public function offsetSet($offset, $value)
	{
		return $this->slots->offsetSet($offset, $value);
	}
	
	public function offsetUnset($offset)
	{
		return $this->slots->offsetUnset($offset);
	}
	
	public function countCards() 
	{
		$count = 0;
		foreach($this->slots as $slot) {
			$count += $slot->getQuantity();
		}
		return $count;
	}
	
	public function getIncludedSets() {
		$sets = [];
		foreach ($this->slots as $slot) {
			$card = $slot->getCard();
			$set = $card->getSet();
			if(!isset($sets[$set->getPosition()])) {
				$sets[$set->getPosition()] = [
					'set' => $set,
					'nb' => 1
				];
			}
		}
		ksort($sets);
		return array_values($sets);
	}

	public function getSlotByCode($code) {
		foreach($this->slots as $slot) {
			if($slot->getCard()->getCode() == $code) {
				return $slot;
			}
		}
		return NULL;
	}

	public function isSlotIncluded($code) {
		$slot = $this->getSlotByCode($code);
		return $slot != NULL;
	}
	
	public function getSlotsByType() {
		$slotsByType = [ 'battlefield' => [], 'plot' => [], 'upgrade' => [], 'downgrade' => [], 'support' => [], 'event' => [] ];
		foreach($this->slots as $slot) {
			if(array_key_exists($slot->getCard()->getType()->getCode(), $slotsByType)) {
				$slotsByType[$slot->getCard()->getType()->getCode()][] = $slot;
			}
		}
		$slotsByType['character'] = $this->getCharacterArray();
		return $slotsByType;
	}

	public function getSlotsByAffiliation() {
		$getSlotsByAffiliation = [ 'villain' => [], 'hero' => [], 'neutral' => [] ];
		foreach($this->slots as $slot) {
			if(array_key_exists($slot->getCard()->getAffiliation()->getCode(), $getSlotsByAffiliation)) {
				$getSlotsByAffiliation[$slot->getCard()->getAffiliation()->getCode()][] = $slot;
			}
		}
		return $getSlotsByAffiliation;
	}
	
	public function getCountByType() {
		$countByType = [ 
			'upgrade' => array(
				"cards" => 0,
				"dice" => 0),

			'downgrade' => array(
				"cards" => 0,
				"dice" => 0),

			'support' => array(
				"cards" => 0,
				"dice" => 0),

			'event' => array(
				"cards" => 0,
				"dice" => 0)];

		foreach($this->slots as $slot) {
			$code = $slot->getCard()->getType()->getCode();
			if(array_key_exists($code, $countByType)) {
				$countByType[$code]["cards"] += $slot->getQuantity();
				$countByType[$code]["dice"] += $slot->getDice();
			}
		}
		return $countByType;
	}

	public function getCountByFaction() {
		$countByFaction = ['red' => 0, 'yellow' => 0, 'blue' => 0];

		foreach($this->slots as $slot) {
			$code = $slot->getCard()->getFaction()->getCode();
			if(array_key_exists($code, $countByFaction)) {
				$countByFaction[$code] += max($slot->getQuantity(), $slot->getDice());
			}
		}
		return $countByFaction;
	}

	public function getCountByAffiliation() {
		$countByAffiliation = ['villain' => 0, 'hero' => 0];

		foreach($this->slots as $slot) {
			$code = $slot->getCard()->getAffiliation()->getCode();
			if(array_key_exists($code, $countByAffiliation)) {
				$countByAffiliation[$code] += max($slot->getQuantity(), $slot->getDice());
			}
		}
		return $countByAffiliation;
	}

	public function getBattlefieldDeck()
	{
		$battlefieldDeck = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'battlefield') {
				$battlefieldDeck[] = $slot;
			}
		}
		return new SlotCollectionDecorator(new ArrayCollection($battlefieldDeck));
	}

	public function getDrawDeck()
	{
		$drawDeck = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'upgrade'
			|| $slot->getCard()->getType()->getCode() === 'downgrade'
			|| $slot->getCard()->getType()->getCode() === 'support'
			|| $slot->getCard()->getType()->getCode() === 'event') {
				$drawDeck[] = $slot;
			}
		}
		return new SlotCollectionDecorator(new ArrayCollection($drawDeck));
	}

	public function getCharacterDeck()
	{
		$characterDeck = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'character') {
				$characterDeck[] = $slot;
			}
		}
		return new SlotCollectionDecorator(new ArrayCollection($characterDeck));
	}

	public function getCharacterArray()
	{
		$characterRow = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'character') {
				if($slot->getCard()->getIsUnique()) {
					$characterRow[] = $slot;
				} else if($slot instanceof Deckslot && $slot->getDices()) {
					foreach(explode(",", $slot->getDices()) as $i) {
						$slot->setDice($i);
						$slot->setQuantity(1);
						$characterRow[] = clone $slot;
					}
				} else {
					$totalCards = $slot->getQuantity();
					$slot->setDice(1);
					$slot->setQuantity(1);
					for($i = 0; $i < $totalCards; $i++) {
						$characterRow[] = $slot;
					}
				}
			}
		}
		return $characterRow;
	}

	public function getCharacterRow()
	{
		return new SlotCollectionDecorator(new ArrayCollection($this->getCharacterArray()));
	}

	public function getCharacterPoints()
	{
		$points = 0;
		forEach($this->slots as $slot)
		{
			$card = $slot->getCard();
			if($card->getType()->getCode() != 'character') continue;

			$formatPoints = $card->getPoints();
			if($this->format && array_key_exists($card->getCode(), $this->format->getData()['balance']))
			{
				$formatPoints = $this->format->getData()["balance"][$card->getCode()];
			}

			$inc = 0;
			if($card->getIsUnique())
			{
				$pointValues = preg_split('/\//', $formatPoints);
				$inc = intval($pointValues[$slot->getDice()-1], 10);
			}
			else
			{
				$inc = intval($formatPoints) * $slot->getQuantity();
			}

			$points += $inc;
		};

		//if Clone Commander Cody (AtG #73)
		if($this->isSlotIncluded("08073"))
		{
			//every Clone Trooper (LEG #38) cost 1 point less
			foreach($this->getCharacterDeck()->getSlots() as $slot)
			{
				if($slot->getCard()->getCode()=="05038") {
					$points -=  $slot->getQuantity();
				}
			}
		}

		//if General Grievous - Droid Armies Commander (CONV #21)
		if($this->isSlotIncluded("09021"))
		{
			//every droid cost 1 point less
			foreach($this->getCharacterDeck()->getSlots() as $slot)
			{
				foreach($slot->getCard()->getSubtypes() as $subtype)
				{
					if($subtype->getCode() == 'droid')
					{
						$points -= $slot->getQuantity();
						break;
					}
				}
			}
		}

		//if Kanan Jarrus - Jedi Exile (CONV #55)
		if($this->isSlotIncluded("12055"))
		{
			//reduce cost if there is other spectre
			$otherSpectre = false;
			foreach($this->getCharacterDeck()->getSlots() as $slot)
			{
				if($slot->getCard()->getCode() !== '12005') {
					foreach($slot->getCard()->getSubtypes() as $subtype)
					{
						if($subtype->getCode() == 'spectre')
						{
							$otherSpectre = true;
							break;
						}
					}
				}
			}
			if($otherSpectre) {
				$points -= 1;
			}
		}

		return $points;
	}

	public function getPlotDeck()
	{
		$plotDeck = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'plot') {
				$plotDeck[] = $slot;
			}
		}
		return new SlotCollectionDecorator(new ArrayCollection($plotDeck));
	}

	public function getPlotPoints()
	{
		$points = 0;
		forEach($this->slots as $slot)
		{
			$card = $slot->getCard();
			if($card->getType()->getCode() != 'plot') continue;

			$points += intval($card->getPoints()) * $slot->getQuantity();
		};

		//if Director Krennic - Death Star Mastermind (CM #21)
		if($this->isSlotIncluded("12021"))
		{
			//every death star plot cost 1 point less
			foreach($this->getPlotDeck()->getSlots() as $slot)
			{
				foreach($slot->getCard()->getSubtypes() as $subtype)
				{
					if($subtype->getCode() == 'death-star')
					{
						$points -= $slot->getQuantity();
						break;
					}
				}
			}
		}

		//if Luke Skywalker - Red Five (CM #56)
		if($this->isSlotIncluded("12056"))
		{
			//every death star plot cost 1 point less
			foreach($this->getPlotDeck()->getSlots() as $slot)
			{
				foreach($slot->getCard()->getSubtypes() as $subtype)
				{
					if($subtype->getCode() == 'death-star')
					{
						$points -= $slot->getQuantity();
						break;
					}
				}
			}
		}

		return $points;
	}

	public function getFactions()
	{
		$factions = [];
		forEach($this->slots AS $slot)
		{
			$factions[] = $slot->getCard()->getFaction()->getCode();
		}
		return array_unique($factions);
	}
	
	public function getCopiesAndDeckLimit()
	{
		$copiesAndDeckLimit = [];
		foreach($this->getDrawDeck()->getSlots() as $slot) {
			$cardName = $slot->getCard()->getName();
			if(!key_exists($cardName, $copiesAndDeckLimit)) {
				$copiesAndDeckLimit[$cardName] = [
					'copies' => $slot->getQuantity(),
					'deck_limit' => $slot->getCard()->getDeckLimit(),
				];
			} else {
				$copiesAndDeckLimit[$cardName]['copies'] += $slot->getQuantity();
				$copiesAndDeckLimit[$cardName]['deck_limit'] = min($slot->getCard()->getDeckLimit(), $copiesAndDeckLimit[$cardName]['deck_limit']);
			}
		}
		return $copiesAndDeckLimit;
	}
	
	public function getSlots()
	{
		return $this->slots;
	}

	public function getContent()
	{
		$arr = array ();
		foreach ( $this->slots as $slot ) {
			if($slot instanceof Deckslot) {
				$arr [$slot->getCard()->getCode()] = array(
					"quantity" => $slot->getQuantity(),
					"dice" => $slot->getDice(),
					"dices" => $slot->getDices()
				);
			} else {
				$arr [$slot->getCard()->getCode()] = array(
					"quantity" => $slot->getQuantity(),
					"dice" => $slot->getDice()
				);
			}
		}
		ksort ( $arr );
		return $arr;
	}
	
}
