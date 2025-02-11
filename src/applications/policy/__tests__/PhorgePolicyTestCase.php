<?php

final class PhorgePolicyTestCase extends PhorgeTestCase {

  /**
   * Verify that any user can view an object with POLICY_PUBLIC.
   */
  public function testPublicPolicyEnabled() {
    $env = PhorgeEnv::beginScopedEnv();
    $env->overrideEnvConfig('policy.allow-public', true);

    $this->expectVisibility(
      $this->buildObject(PhorgePolicies::POLICY_PUBLIC),
      array(
        'public'  => true,
        'user'    => true,
        'admin'   => true,
      ),
      pht('Public Policy (Enabled in Config)'));
  }


  /**
   * Verify that POLICY_PUBLIC is interpreted as POLICY_USER when public
   * policies are disallowed.
   */
  public function testPublicPolicyDisabled() {
    $env = PhorgeEnv::beginScopedEnv();
    $env->overrideEnvConfig('policy.allow-public', false);

    $this->expectVisibility(
      $this->buildObject(PhorgePolicies::POLICY_PUBLIC),
      array(
        'public'  => false,
        'user'    => true,
        'admin'   => true,
      ),
      pht('Public Policy (Disabled in Config)'));
  }


  /**
   * Verify that any logged-in user can view an object with POLICY_USER, but
   * logged-out users can not.
   */
  public function testUsersPolicy() {
    $this->expectVisibility(
      $this->buildObject(PhorgePolicies::POLICY_USER),
      array(
        'public'  => false,
        'user'    => true,
        'admin'   => true,
      ),
      pht('User Policy'));
  }


  /**
   * Verify that only administrators can view an object with POLICY_ADMIN.
   */
  public function testAdminPolicy() {
    $this->expectVisibility(
      $this->buildObject(PhorgePolicies::POLICY_ADMIN),
      array(
        'public'  => false,
        'user'    => false,
        'admin'   => true,
      ),
      pht('Admin Policy'));
  }


  /**
   * Verify that no one can view an object with POLICY_NOONE.
   */
  public function testNoOnePolicy() {
    $this->expectVisibility(
      $this->buildObject(PhorgePolicies::POLICY_NOONE),
      array(
        'public'  => false,
        'user'    => false,
        'admin'   => false,
      ),
      pht('No One Policy'));
  }


  /**
   * Test offset-based filtering.
   */
  public function testOffsets() {
    $results = array(
      $this->buildObject(PhorgePolicies::POLICY_NOONE),
      $this->buildObject(PhorgePolicies::POLICY_NOONE),
      $this->buildObject(PhorgePolicies::POLICY_NOONE),
      $this->buildObject(PhorgePolicies::POLICY_USER),
      $this->buildObject(PhorgePolicies::POLICY_USER),
      $this->buildObject(PhorgePolicies::POLICY_USER),
    );

    $query = new PhorgePolicyAwareTestQuery();
    $query->setResults($results);
    $query->setViewer($this->buildUser('user'));

    $this->assertEqual(
      3,
      count($query->setLimit(3)->setOffset(0)->execute()),
      pht('Invisible objects are ignored.'));

    $this->assertEqual(
      0,
      count($query->setLimit(3)->setOffset(3)->execute()),
      pht('Offset pages through visible objects only.'));

    $this->assertEqual(
      2,
      count($query->setLimit(3)->setOffset(1)->execute()),
      pht('Offsets work correctly.'));

    $this->assertEqual(
      2,
      count($query->setLimit(0)->setOffset(1)->execute()),
      pht('Offset with no limit works.'));
  }


  /**
   * Test limits.
   */
  public function testLimits() {
    $results = array(
      $this->buildObject(PhorgePolicies::POLICY_USER),
      $this->buildObject(PhorgePolicies::POLICY_USER),
      $this->buildObject(PhorgePolicies::POLICY_USER),
      $this->buildObject(PhorgePolicies::POLICY_USER),
      $this->buildObject(PhorgePolicies::POLICY_USER),
      $this->buildObject(PhorgePolicies::POLICY_USER),
    );

    $query = new PhorgePolicyAwareTestQuery();
    $query->setResults($results);
    $query->setViewer($this->buildUser('user'));

    $this->assertEqual(
      3,
      count($query->setLimit(3)->setOffset(0)->execute()),
      pht('Limits work.'));

    $this->assertEqual(
      2,
      count($query->setLimit(3)->setOffset(4)->execute()),
      pht('Limit + offset work.'));
  }


  /**
   * Test that omnipotent users bypass policies.
   */
  public function testOmnipotence() {
    $results = array(
      $this->buildObject(PhorgePolicies::POLICY_NOONE),
    );

    $query = new PhorgePolicyAwareTestQuery();
    $query->setResults($results);
    $query->setViewer(PhorgeUser::getOmnipotentUser());

    $this->assertEqual(
      1,
      count($query->execute()));
  }


  /**
   * Test that invalid policies reject viewers of all types.
   */
  public function testRejectInvalidPolicy() {
    $invalid_policy = 'the duck goes quack';
    $object = $this->buildObject($invalid_policy);

    $this->expectVisibility(
      $object = $this->buildObject($invalid_policy),
      array(
        'public'  => false,
        'user'    => false,
        'admin'   => false,
      ),
      pht('Invalid Policy'));
  }


  /**
   * Test that extended policies work.
   */
  public function testExtendedPolicies() {
    $object = $this->buildObject(PhorgePolicies::POLICY_USER)
      ->setPHID('PHID-TEST-1');

    $this->expectVisibility(
      $object,
      array(
        'public'  => false,
        'user'    => true,
        'admin'   => true,
      ),
      pht('No Extended Policy'));

    // Add a restrictive extended policy.
    $extended = $this->buildObject(PhorgePolicies::POLICY_ADMIN)
      ->setPHID('PHID-TEST-2');
    $object->setExtendedPolicies(
      array(
        PhorgePolicyCapability::CAN_VIEW => array(
          array($extended, PhorgePolicyCapability::CAN_VIEW),
        ),
      ));

    $this->expectVisibility(
      $object,
      array(
        'public'  => false,
        'user'    => false,
        'admin'   => true,
      ),
      pht('With Extended Policy'));

    // Depend on a different capability.
    $object->setExtendedPolicies(
      array(
        PhorgePolicyCapability::CAN_VIEW => array(
          array($extended, PhorgePolicyCapability::CAN_EDIT),
        ),
      ));

    $extended->setCapabilities(array(PhorgePolicyCapability::CAN_EDIT));
    $extended->setPolicies(
      array(
        PhorgePolicyCapability::CAN_EDIT =>
          PhorgePolicies::POLICY_NOONE,
      ));

    $this->expectVisibility(
      $object,
      array(
        'public'  => false,
        'user'    => false,
        'admin'   => false,
      ),
      pht('With Extended Policy + Edit'));
  }


  /**
   * Test that cyclic extended policies are arrested properly.
   */
  public function testExtendedPolicyCycles() {
    $object = $this->buildObject(PhorgePolicies::POLICY_USER)
      ->setPHID('PHID-TEST-1');

    $this->expectVisibility(
      $object,
      array(
        'public'  => false,
        'user'    => true,
        'admin'   => true,
      ),
      pht('No Extended Policy'));

    // Set a self-referential extended policy on the object. This should
    // make it fail all policy checks.
    $object->setExtendedPolicies(
      array(
        PhorgePolicyCapability::CAN_VIEW => array(
          array($object, PhorgePolicyCapability::CAN_VIEW),
        ),
      ));

    $this->expectVisibility(
      $object,
      array(
        'public'  => false,
        'user'    => false,
        'admin'   => false,
      ),
      pht('Extended Policy with Cycle'));
  }


  /**
   * Test bulk checks of extended policies.
   *
   * This is testing an issue with extended policy filtering which allowed
   * unusual inputs to slip objects through the filter. See D14993.
   */
  public function testBulkExtendedPolicies() {
    $object1 = $this->buildObject(PhorgePolicies::POLICY_USER)
      ->setPHID('PHID-TEST-1');
    $object2 = $this->buildObject(PhorgePolicies::POLICY_USER)
      ->setPHID('PHID-TEST-2');
    $object3 = $this->buildObject(PhorgePolicies::POLICY_USER)
      ->setPHID('PHID-TEST-3');

    $extended = $this->buildObject(PhorgePolicies::POLICY_ADMIN)
      ->setPHID('PHID-TEST-999');

    $object1->setExtendedPolicies(
      array(
        PhorgePolicyCapability::CAN_VIEW => array(
          array(
            $extended,
            array(
              PhorgePolicyCapability::CAN_VIEW,
              PhorgePolicyCapability::CAN_EDIT,
            ),
          ),
        ),
      ));

    $object2->setExtendedPolicies(
      array(
        PhorgePolicyCapability::CAN_VIEW => array(
          array($extended, PhorgePolicyCapability::CAN_VIEW),
        ),
      ));

    $object3->setExtendedPolicies(
      array(
        PhorgePolicyCapability::CAN_VIEW => array(
          array(
            $extended,
            array(
              PhorgePolicyCapability::CAN_VIEW,
              PhorgePolicyCapability::CAN_EDIT,
            ),
          ),
        ),
      ));

    $user = $this->buildUser('user');

    $visible = id(new PhorgePolicyFilter())
      ->setViewer($user)
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
        ))
      ->apply(
        array(
          $object1,
          $object2,
          $object3,
        ));

    $this->assertEqual(array(), $visible);
  }


  /**
   * An omnipotent user should be able to see even objects with invalid
   * policies.
   */
  public function testInvalidPolicyVisibleByOmnipotentUser() {
    $invalid_policy = 'the cow goes moo';
    $object = $this->buildObject($invalid_policy);

    $results = array(
      $object,
    );

    $query = new PhorgePolicyAwareTestQuery();
    $query->setResults($results);
    $query->setViewer(PhorgeUser::getOmnipotentUser());

    $this->assertEqual(
      1,
      count($query->execute()));
  }

  public function testAllQueriesBelongToActualApplications() {
    $queries = id(new PhutilClassMapQuery())
      ->setAncestorClass('PhorgePolicyAwareQuery')
      ->execute();

    foreach ($queries as $qclass => $query) {
      $class = $query->getQueryApplicationClass();
      if (!$class) {
        continue;
      }
      $this->assertTrue(
        (bool)PhorgeApplication::getByClass($class),
        pht(
          "Application class '%s' for query '%s'.",
          $class,
          $qclass));
    }
  }

  public function testMultipleCapabilities() {
    $object = new PhorgePolicyTestObject();
    $object->setCapabilities(
      array(
        PhorgePolicyCapability::CAN_VIEW,
        PhorgePolicyCapability::CAN_EDIT,
      ));
    $object->setPolicies(
      array(
        PhorgePolicyCapability::CAN_VIEW
          => PhorgePolicies::POLICY_USER,
        PhorgePolicyCapability::CAN_EDIT
          => PhorgePolicies::POLICY_NOONE,
      ));

    $filter = new PhorgePolicyFilter();
    $filter->requireCapabilities(
      array(
        PhorgePolicyCapability::CAN_VIEW,
        PhorgePolicyCapability::CAN_EDIT,
      ));
    $filter->setViewer($this->buildUser('user'));

    $result = $filter->apply(array($object));

    $this->assertEqual(array(), $result);
  }

  public function testPolicyStrength() {
    $public = PhorgePolicyQuery::getGlobalPolicy(
      PhorgePolicies::POLICY_PUBLIC);
    $user = PhorgePolicyQuery::getGlobalPolicy(
      PhorgePolicies::POLICY_USER);
    $admin = PhorgePolicyQuery::getGlobalPolicy(
      PhorgePolicies::POLICY_ADMIN);
    $noone = PhorgePolicyQuery::getGlobalPolicy(
      PhorgePolicies::POLICY_NOONE);

    $this->assertFalse($public->isStrongerThan($public));
    $this->assertFalse($public->isStrongerThan($user));
    $this->assertFalse($public->isStrongerThan($admin));
    $this->assertFalse($public->isStrongerThan($noone));

    $this->assertTrue($user->isStrongerThan($public));
    $this->assertFalse($user->isStrongerThan($user));
    $this->assertFalse($user->isStrongerThan($admin));
    $this->assertFalse($user->isStrongerThan($noone));

    $this->assertTrue($admin->isStrongerThan($public));
    $this->assertTrue($admin->isStrongerThan($user));
    $this->assertFalse($admin->isStrongerThan($admin));
    $this->assertFalse($admin->isStrongerThan($noone));

    $this->assertTrue($noone->isStrongerThan($public));
    $this->assertTrue($noone->isStrongerThan($user));
    $this->assertTrue($noone->isStrongerThan($admin));
    $this->assertFalse($admin->isStrongerThan($noone));
  }


  /**
   * Test an object for visibility across multiple user specifications.
   */
  private function expectVisibility(
    PhorgePolicyTestObject $object,
    array $map,
    $description) {

    foreach ($map as $spec => $expect) {
      $viewer = $this->buildUser($spec);

      $query = new PhorgePolicyAwareTestQuery();
      $query->setResults(array($object));
      $query->setViewer($viewer);

      $caught = null;
      $result = null;
      try {
        $result = $query->executeOne();
      } catch (PhorgePolicyException $ex) {
        $caught = $ex;
      }

      if ($expect) {
        $this->assertEqual(
          $object,
          $result,
          pht('%s with user %s should succeed.', $description, $spec));
      } else {
        $this->assertTrue(
          $caught instanceof PhorgePolicyException,
          pht('%s with user %s should fail.', $description, $spec));
      }
    }
  }


  /**
   * Build a test object to spec.
   */
  private function buildObject($policy) {
    $object = new PhorgePolicyTestObject();
    $object->setCapabilities(
      array(
        PhorgePolicyCapability::CAN_VIEW,
        PhorgePolicyCapability::CAN_EDIT,
      ));
    $object->setPolicies(
      array(
        PhorgePolicyCapability::CAN_VIEW => $policy,
        PhorgePolicyCapability::CAN_EDIT => $policy,
      ));

    return $object;
  }


  /**
   * Build a test user to spec.
   */
  private function buildUser($spec) {
    $user = new PhorgeUser();

    switch ($spec) {
      case 'public':
        break;
      case 'user':
        $user->setPHID(1);
        break;
      case 'admin':
        $user->setPHID(1);
        $user->setIsAdmin(true);
        break;
      default:
        throw new Exception(pht("Unknown user spec '%s'.", $spec));
    }

    return $user;
  }

}
