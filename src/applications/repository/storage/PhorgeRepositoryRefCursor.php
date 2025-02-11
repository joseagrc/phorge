<?php

/**
 * Stores the previous value of a ref (like a branch or tag) so we can figure
 * out how a repository has changed when we discover new commits or branch
 * heads.
 */
final class PhorgeRepositoryRefCursor
  extends PhorgeRepositoryDAO
  implements PhorgePolicyInterface {

  const TYPE_BRANCH = 'branch';
  const TYPE_TAG = 'tag';
  const TYPE_BOOKMARK = 'bookmark';
  const TYPE_REF = 'ref';

  protected $repositoryPHID;
  protected $refType;
  protected $refNameHash;
  protected $refNameRaw;
  protected $refNameEncoding;
  protected $isPermanent;

  private $repository = self::ATTACHABLE;
  private $positions = self::ATTACHABLE;

  protected function getConfiguration() {
    return array(
      self::CONFIG_TIMESTAMPS => false,
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_BINARY => array(
        'refNameRaw' => true,
      ),
      self::CONFIG_COLUMN_SCHEMA => array(
        'refType' => 'text32',
        'refNameHash' => 'bytes12',
        'refNameEncoding' => 'text16?',
        'isPermanent' => 'bool',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_ref' => array(
          'columns' => array('repositoryPHID', 'refType', 'refNameHash'),
          'unique' => true,
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhorgePHID::generateNewPHID(
      PhorgeRepositoryRefCursorPHIDType::TYPECONST);
  }

  public function getRefName() {
    return $this->getUTF8StringFromStorage(
      $this->getRefNameRaw(),
      $this->getRefNameEncoding());
  }

  public function setRefName($ref_raw) {
    $this->setRefNameRaw($ref_raw);
    $this->setRefNameHash(PhorgeHash::digestForIndex($ref_raw));
    $this->setRefNameEncoding($this->detectEncodingForStorage($ref_raw));

    return $this;
  }

  public function attachRepository(PhorgeRepository $repository) {
    $this->repository = $repository;
    return $this;
  }

  public function getRepository() {
    return $this->assertAttached($this->repository);
  }

  public function attachPositions(array $positions) {
    assert_instances_of($positions, 'PhorgeRepositoryRefPosition');
    $this->positions = $positions;
    return $this;
  }

  public function getPositions() {
    return $this->assertAttached($this->positions);
  }

  public function getPositionIdentifiers() {
    return mpull($this->getPositions(), 'getCommitIdentifier');
  }

  public function newDiffusionRepositoryRef() {
    return id(new DiffusionRepositoryRef())
      ->setRefType($this->getRefType())
      ->setShortName($this->getRefName());
  }


/* -(  PhorgePolicyInterface  )----------------------------------------- */


  public function getCapabilities() {
    return array(
      PhorgePolicyCapability::CAN_VIEW,
    );
  }

  public function getPolicy($capability) {
    return $this->getRepository()->getPolicy($capability);
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    return $this->getRepository()->hasAutomaticCapability($capability, $viewer);
  }

  public function describeAutomaticCapability($capability) {
    return pht('Repository refs have the same policies as their repository.');
  }

}
