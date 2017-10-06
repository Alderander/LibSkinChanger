<?php

declare(strict_types = 1);

namespace BlockHorizons\LibSkinChanger;

use BlockHorizons\LibSkinChanger\SkinComponents\Body;
use BlockHorizons\LibSkinChanger\SkinComponents\Hat;
use BlockHorizons\LibSkinChanger\SkinComponents\Head;
use BlockHorizons\LibSkinChanger\SkinComponents\Helmet;
use BlockHorizons\LibSkinChanger\SkinComponents\Jacket;
use BlockHorizons\LibSkinChanger\SkinComponents\LeftArm;
use BlockHorizons\LibSkinChanger\SkinComponents\LeftLeg;
use BlockHorizons\LibSkinChanger\SkinComponents\LeftPants;
use BlockHorizons\LibSkinChanger\SkinComponents\LeftSleeve;
use BlockHorizons\LibSkinChanger\SkinComponents\RightArm;
use BlockHorizons\LibSkinChanger\SkinComponents\RightLeg;
use BlockHorizons\LibSkinChanger\SkinComponents\RightPants;
use BlockHorizons\LibSkinChanger\SkinComponents\RightSleeve;
use BlockHorizons\LibSkinChanger\SkinComponents\SkinComponent;
use pocketmine\entity\Skin;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Binary;

class PlayerSkin {

	/**
	 * All skins have these body components.
	 */
	const HEAD = 0;
	const HAT = 1;
	const RIGHT_LEG = 2;
	const BODY = 3;
	const RIGHT_ARM = 4;

	/**
	 * These components are found in 64x64 sized skins only.
	 */
	const RIGHT_PANTS = 5;
	const JACKET = 6;
	const RIGHT_SLEEVE = 7;
	const LEFT_ARM = 8;
	const LEFT_SLEEVE = 9;
	const LEFT_LEG = 10;
	const LEFT_PANTS = 11;

	/**
	 * These components have geometry data, but do not have a skin field.
	 */
	const HELMET = 12;

	/**
	 * Skin components for 64x64 skins.
	 */
	const COMPONENTS_LARGE_FORMAT = [
		self::HEAD => Head::class,
		self::HAT => Hat::class,
		self::RIGHT_LEG => RightLeg::class,
		self::BODY => Body::class,
		self::RIGHT_ARM => RightArm::class,
		self::RIGHT_PANTS => RightPants::class,
		self::JACKET => Jacket::class,
		self::RIGHT_SLEEVE => RightSleeve::class,
		self::LEFT_ARM => LeftArm::class,
		self::LEFT_SLEEVE => LeftSleeve::class,
		self::LEFT_LEG => LeftLeg::class,
		self::LEFT_PANTS => LeftPants::class,

		self::HELMET => Helmet::class
	];

	/**
	 * Skin components for 64x32 skins.
	 */
	const COMPONENTS_SMALL_FORMAT = [
		self::HEAD => Head::class,
		self::HAT => Hat::class,
		self::RIGHT_LEG => RightLeg::class,
		self::BODY => Body::class,
		self::RIGHT_ARM => RightArm::class,

		self::HELMET => Helmet::class
	];

	/** @var SkinPixel[] */
	private $pixels = [];
	/** @var int */
	private $skinWidth = 64;
	/** @var SkinComponent[] */
	private $skinComponents = [];
	/** @var string */
	private $geometryName = "";

	public function __construct(Skin $skin) {
		$skinData = $skin->getSkinData();
		$geometryData = $skin->getGeometryData();
		$geometryName = $skin->getGeometryName();
		$stream = new BinaryStream($skinData);
		$this->skinHeight = strlen($skinData) === 16384 ? 64 : 32;
		for($x = 0; $x < $this->skinWidth; $x++) {
			for($y = 0; $y < $this->skinHeight; $y++) {
				if(!$stream->feof()) {
					$this->pixels[($y << 6) | $x] = new SkinPixel($stream->getByte(), $stream->getByte(), $stream->getByte(), $stream->getByte());
				}
			}
		}
		if(empty($geometryData)) {
			$geometryData = file_get_contents(__DIR__ . "/default_geometry.json");
			$geometryName = "geometry.humanoid";
		}
		$geometry = json_decode($geometryData, true)[$geometryName];
		if($this->skinHeight === 64) {
			foreach(self::COMPONENTS_LARGE_FORMAT as $key => $component) {
				$this->skinComponents[$key] = new $component($this, $geometry);
			}
		}
		$this->geometryName = $geometryName;
	}

	/**
	 * @return SkinComponent[]
	 */
	public function getSkinComponents(): array {
		return $this->skinComponents;
	}

	/**
	 * @param int $id
	 *
	 * @return SkinComponent|null
	 */
	public function getComponent(int $id): ?SkinComponent {
		return $this->skinComponents[$id] ?? null;
	}

	/**
	 * @param int $x
	 * @param int $y
	 *
	 * @return SkinPixel|null
	 */
	public function getPixelAt(int $x, int $y): ?SkinPixel {
		if($x > $this->skinWidth || $y > $this->skinHeight) {
			throw new \InvalidArgumentException("Pixel coordinates should be ranged 0 - 64");
		}
		if($x < 0 || $y < 0) {
			throw new \InvalidArgumentException("Pixel coordinates should be ranged 0 - 64");
		}
		return $this->pixels[($y << 6) | $x] ?? null;
	}

	/**
	 * @return int
	 */
	public function getSkinWidth(): int {
		return $this->skinWidth;
	}

	/**
	 * @return int
	 */
	public function getSkinHeight(): int {
		return $this->skinHeight;
	}

	/**
	 * @return string
	 */
	public function getSkinData(): string {
		$str = "";
		foreach($this->pixels as $pixel) {
			$str .= Binary::writeByte($pixel->getR()) . Binary::writeByte($pixel->getG()) . Binary::writeByte($pixel->getB()) . Binary::writeByte($pixel->getA());
		}
		return $str;
	}

	/**
	 * @param string $geometryName
	 *
	 * @return string
	 */
	public function getGeometryJson(string $geometryName): string {
		$json = [];
		foreach($this->getSkinComponents() as $component) {
			$json[$geometryName]["bones"][] = $component->getGeometry()->jsonSerialize();
		}
		return json_encode($json);
	}

	/**
	 * @return string
	 */
	public function getGeometryName(): string {
		return $this->geometryName;
	}
}