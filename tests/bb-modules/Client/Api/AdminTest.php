<?php

namespace Box\Tests\Mod\Client\Api;

class AdminTest extends \PHPUnit_Framework_TestCase {

    public function testgetDi()
    {
        $di = new \Box_Di();
        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);
        $getDi = $admin_Client->getDi();
        $this->assertEquals($di, $getDi);
    }

    public function testget_list()
    {
        $simpleResultArr = array(
            'list' => array(
                array('id' => 1),
            ),
        );
        $pagerMock       = $this->getMockBuilder('\Box_Pagination')->disableOriginalConstructor()->getMock();
        $pagerMock->expects($this->atLeastOnce())
            ->method('getSimpleResultSet')
            ->will($this->returnValue($simpleResultArr));

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
        method('getSearchQuery');
        $serviceMock->expects($this->atLeastOnce())->
        method('toApiArray')
            ->will($this->returnValue(array()));

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());
        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getExistingModelById')
            ->will($this->returnValue($model));

        $di                = new \Box_Di();
        $di['pager']       = $pagerMock;
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setService($serviceMock);
        $admin_Client->setDi($di);
        $data = array();

        $result = $admin_Client->get_list($data);
        $this->assertInternalType('array', $result);

    }

    public function test_get_pairs()
    {
        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('getPairs')->will($this->returnValue(array()));


        $di = new \Box_Di();
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $data = array('id' => 1);
        $result = $admin_Client->get_pairs($data);
        $this->assertInternalType('array', $result);
    }

    public function testget()
    {
        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('get')->will($this->returnValue($model));
        $serviceMock->expects($this->atLeastOnce())
            ->method('toApiArray')
            ->will($this->returnValue(array()));

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setService($serviceMock);

        $result = $admin_Client->get(array());
        $this->assertInternalType('array', $result);
    }

    public function testlogin()
    {
        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());
        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $sessionArray = array(
            'id'        =>  1,
            'email'     =>  'email@example.com',
            'name'      =>  'John Smith',
            'role'      =>  'client',
        );
        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('toSessionArray')->will($this->returnValue($sessionArray));

        $sessionMock = $this->getMockBuilder('\Box_Session')->disableOriginalConstructor()->getMock();
        $sessionMock->expects($this->atLeastOnce())->
            method('set');

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});
        $di['session'] = $sessionMock;
        $di['logger'] = new \Box_Log();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $data = array('id' => 1);
        $result =  $admin_Client->login($data);
        $this->assertInternalType('array', $result);
    }

    public function testloginMissingIdException()
    {
        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $data = array();
        $this->setExpectedException('\Box_Exception', 'ID required');
        $result =  $admin_Client->login($data);
    }

    public function testloginClientNotFound()
    {
        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(array()));

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $data = array('id' => 1);
        $this->setExpectedException('\Box_Exception', 'Client not found');
        $result =  $admin_Client->login($data);

    }

    public function testCreate()
    {
        $data = array(
            'email' => 'email@example.com',
            'first_name' => 'John',
        );

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('emailAreadyRegistered')->will($this->returnValue(false));
        $serviceMock->expects($this->atLeastOnce())->
            method('adminCreateClient')->will($this->returnValue(1));

        $validatorMock = $this->getMockBuilder('\Box_Validate')->getMock();
        $validatorMock->expects($this->atLeastOnce())->method('isEmailValid');

        $di = new \Box_Di();
        $di['validator'] = $validatorMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);
        $admin_Client->setService($serviceMock);

        $result = $admin_Client->create($data);

        $this->assertInternalType('int', $result, 'create() returned: '.$result);
    }

    public function testCreateEmailException()
    {
        $data = array();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $this->setExpectedException('\Box_Exception', 'Email required');
        $admin_Client->create($data);
    }

    public function testCreateFirstNameException()
    {
        $data = array('email' => 'test@example.com');

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $this->setExpectedException('\Box_Exception', 'First name is required');
        $admin_Client->create($data);
    }

    public function testCreateEmailRegisteredException()
    {
        $data = array(
            'email' => 'email@example.com',
            'first_name' => 'John',
        );

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('emailAreadyRegistered')->will($this->returnValue(true));

        $validatorMock = $this->getMockBuilder('\Box_Validate')->getMock();
        $validatorMock->expects($this->atLeastOnce())->method('isEmailValid');

        $di = new \Box_Di();
        $di['validator'] = $validatorMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);
        $admin_Client->setService($serviceMock);

        $this->setExpectedException('\Box_Exception', 'Email is already registered.');
        $admin_Client->create($data);
    }

    public function testdelete()
    {
        $data = array ('id' => 1);

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $eventMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventMock->expects($this->atLeastOnce())->
            method('fire');

        $serviceMock = $this->getMockBuilder('\Box\Client\Service')
            ->setMethods(array('remove'))
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('remove')
            ->will($this->returnValue(true));

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['events_manager'] = $eventMock;
        $di['logger'] = new \Box_Log();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);
        $admin_Client->setService($serviceMock);
        $result = $admin_Client->delete($data);
        $this->assertTrue($result);
    }

    public function testdeleteIdException()
    {
        $data = array ();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $this->setExpectedException('\Box_Exception', 'Client id is missing');
        $admin_Client->delete($data);
    }

    public function testdeleteClientNotFoundException()
    {
        $data = array ('id' => 1);

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(null));
        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Client not found');
        $admin_Client->delete($data);
    }

    public function testupdate()
    {
        $data = array(
            'id'             => 1,
            'first_name'     => 'John',
            'last_name'      => 'Smith',
            'aid'            => '0',
            'gender'         => 'male',
            'birthday'       => '1999-01-01',
            'company'        => 'LTD Testing',
            'company_vat'    => 'VAT0007',
            'address_1'      => 'United States',
            'address_2'      => 'Utah',
            'phone_cc'       => '+1',
            'phone'          => '555-345-345',
            'document_type'  => 'doc',
            'document_nr'    => '1',
            'notes'          => 'none',
            'country'        => 'Moon',
            'postcode'       => 'IL-11123',
            'city'           => 'Chicaco',
            'state'          => 'IL',
            'currency'       => 'USD',
            'tax_exempt'     => 'n/a',
            'created_at'     => '2012-05-10',
            'email'          => 'test@example.com',
            'group_id'       => 1,
            'status'         => 'test status',
            'company_number' => '1234',
            'type'           => '',
            'lang'           => 'en',
            'custom_1'       => '',
            'custom_2'       => '',
            'custom_3'       => '',
            'custom_4'       => '',
            'custom_5'       => '',
            'custom_6'       => '',
            'custom_7'       => '',
            'custom_8'       => '',
            'custom_9'       => '',
            'custom_10'      => '',
        );

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));
        $dbMock->expects($this->atLeastOnce())
            ->method('store')->will($this->returnValue(1));

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('emailAreadyRegistered')->will($this->returnValue(false));
        $serviceMock->expects($this->atLeastOnce())->
            method('canChangeCurrency')->will($this->returnValue(true));

        $eventMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventMock->expects($this->atLeastOnce())->
            method('fire');

        $validatorMock = $this->getMockBuilder('\Box_Validate')->getMock();
        $validatorMock->expects($this->atLeastOnce())->method('isEmailValid');

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});;
        $di['events_manager'] = $eventMock;
        $di['validator'] = $validatorMock;
        $di['logger'] = new \Box_Log();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);
        $result = $admin_Client->update($data);
        $this->assertTrue($result);
    }

    public function testupdate_EmailALreadyRegistered()
    {
        $data = array(
            'id'             => 1,
            'first_name'     => 'John',
            'last_name'      => 'Smith',
            'aid'            => '0',
            'gender'         => 'male',
            'birthday'       => '1999-01-01',
            'company'        => 'LTD Testing',
            'company_vat'    => 'VAT0007',
            'address_1'      => 'United States',
            'address_2'      => 'Utah',
            'phone_cc'       => '+1',
            'phone'          => '555-345-345',
            'document_type'  => 'doc',
            'document_nr'    => '1',
            'notes'          => 'none',
            'country'        => 'Moon',
            'postcode'       => 'IL-11123',
            'city'           => 'Chicaco',
            'state'          => 'IL',
            'currency'       => 'USD',
            'tax_exempt'     => 'n/a',
            'created_at'     => '2012-05-10',
            'email'          => 'test@example.com',
            'group_id'       => 1,
            'status'         => 'test status',
            'company_number' => '1234',
            'type'           => '',
            'lang'           => 'en',
            'custom_1'       => '',
            'custom_2'       => '',
            'custom_3'       => '',
            'custom_4'       => '',
            'custom_5'       => '',
            'custom_6'       => '',
            'custom_7'       => '',
            'custom_8'       => '',
            'custom_9'       => '',
            'custom_10'      => '',
        );

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('emailAreadyRegistered')->will($this->returnValue(true));
        $serviceMock->expects($this->atLeastOnce())->
            method('canChangeCurrency')->will($this->returnValue(true));

        $eventMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventMock->expects($this->atLeastOnce())->
            method('fire');

        $validatorMock = $this->getMockBuilder('\Box_Validate')->getMock();
        $validatorMock->expects($this->atLeastOnce())->method('isEmailValid');

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});;
        $di['events_manager'] = $eventMock;
        $di['validator'] = $validatorMock;
        $di['logger'] = new \Box_Log();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Can not change email. It is already registered.');
        $admin_Client->update($data);
    }

    public function testUpdateIdException()
    {
        $data = array();
        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $this->setExpectedException('\Box_Exception', 'Id required');
        $admin_Client->update($data);
    }

    public function testUpdateClientNotFoundException()
    {
        $data = array('id' => 1);

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(null));

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Client not found');
        $admin_Client->update($data);
    }

    public function testchange_password()
    {
        $data = array(
            'id' => 1,
            'password' => 'strongPass',
            'password_confirm' => 'strongPass',
        );

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $dbMock->expects($this->atLeastOnce())
            ->method('store')->will($this->returnValue(1));

        $eventMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventMock->expects($this->atLeastOnce())->
            method('fire');

        $passwordMock = $this->getMockBuilder('\Box_Password')->getMock();
        $passwordMock->expects($this->atLeastOnce())
            ->method('hashIt')
            ->with($data['password']);

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['events_manager'] = $eventMock;
        $di['logger'] = new \Box_Log();
        $di['password'] = $passwordMock;


        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->change_password($data);
        $this->assertTrue($result);
    }

    public function testchange_passwordMissingId()
    {
        $data = array();
        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $this->setExpectedException('\Box_Exception', 'Client ID is required');
        $admin_Client->change_password($data);
    }

    public function testchange_passwordMissingPassword()
    {
        $data = array(
            'id' => 1,
        );
        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $this->setExpectedException('\Box_Exception', 'Password required');
        $admin_Client->change_password($data);
    }

    public function testchange_passwordMissingConfirmPass()
    {
        $data = array(
            'id' => 1,
            'password' => 'strongPass',
        );
        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $this->setExpectedException('\Box_Exception', 'Password confirmation required');
        $admin_Client->change_password($data);
    }

    public function testchange_passwordPasswordMismatch()
    {
        $data = array(
            'id' => 1,
            'password' => 'strongPass',
            'password_confirm' => 'NotIdentical',
        );
        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $this->setExpectedException('\Box_Exception', 'Passwords do not match');
        $admin_Client->change_password($data);
    }

    public function testchange_passwordClientNotFound()
    {
        $data = array(
            'id' => 1,
            'password' => 'strongPass',
            'password_confirm' => 'strongPass',
        );

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(null));

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Client not found');
        $admin_Client->change_password($data);
    }

    public function testbalance_get_list()
    {
        $simpleResultArr = array(
            'list' => array(
                array('id' => 1),
            ),
        );

        $data = array();
        $pagerMock = $this->getMockBuilder('\Box_Pagination')->disableOriginalConstructor()->getMock();
        $pagerMock ->expects($this->atLeastOnce())
            ->method('getSimpleResultSet')
            ->will($this->returnValue($simpleResultArr));

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\ServiceBalance')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('getSearchQuery');

        $model = new \Model_ClientBalance();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $di = new \Box_Di();
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});
        $di['pager'] = $pagerMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->balance_get_list($data);
        $this->assertInternalType('array', $result);
    }

    public function testbalance_delete()
    {
        $data = array(
            'id' => 1,
        );

        $model = new \Model_ClientBalance();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $dbMock->expects($this->atLeastOnce())
            ->method('trash');

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['logger'] = new \Box_Log();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->balance_delete($data);
        $this->assertTrue($result);
    }

    public function testbalance_deleteMissingId()
    {
        $data = array();

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $this->setExpectedException('\Box_Exception', 'Client ID is required');
        $admin_Client->balance_delete($data);
    }

    public function testbalance_deleteBalanceNotFound()
    {
        $data = array('id' => 1);

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(null));

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Balance line not found');
        $admin_Client->balance_delete($data);

    }

    public function testbalance_add_funds()
    {
        $data = array(
            'id' => 1,
            'amount' => '1.00',
            'description' => 'testDescription',
        );

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('addFunds');

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->balance_add_funds($data);
        $this->assertTrue($result);
    }

    public function testbalance_add_fundsMissingId()
    {
        $data = array();

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $this->setExpectedException('\Box_Exception', 'Client ID is required');
        $admin_Client->balance_add_funds($data);
    }

    public function testbalance_add_fundsMsssingAmount()
    {
        $data = array(
            'id' => 1,
        );

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $this->setExpectedException('\Box_Exception', 'Amount is required');
        $admin_Client->balance_add_funds($data);
    }
    public function testbalance_add_fundsMissingDescription()
    {
        $data = array(
            'id' => 1,
            'amount' => '1.00',
        );

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $this->setExpectedException('\Box_Exception', 'Description is required');
        $admin_Client->balance_add_funds($data);
    }
    public function testbalance_add_fundsClientNotFound()
    {
        $data = array(
            'id' => 1,
            'amount' => '1.00',
            'description' => 'testDescription',
        );

        $model = new \Model_Client();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(null));


        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Client not found');
        $admin_Client->balance_add_funds($data);
    }

    public function testbatch_expire_password_reminders()
    {
        $expiredArr = array (
            new \Model_ClientPasswordReset(),
        );

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('trash');

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('getExpiredPasswordReminders')->will($this->returnValue($expiredArr));

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});
        $di['logger'] = new \Box_Log();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->batch_expire_password_reminders();
        $this->assertTrue($result);
    }

    public function testlogin_history_get_list()
    {
        $data = array();

        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('getHistorySearchQuery')->will($this->returnValue(array('sql', 'params' )));

        $pagerMock = $this->getMockBuilder('\Box_Pagination')->disableOriginalConstructor()->getMock();
        $pagerResultSet = array(
            'list' => array(),
        );
        $pagerMock ->expects($this->atLeastOnce())
            ->method('getSimpleResultSet')
            ->will($this->returnValue($pagerResultSet));

        $di = new \Box_Di();
        $di['pager'] = $pagerMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);
        $admin_Client->setService($serviceMock);

        $result = $admin_Client->login_history_get_list($data);
        $this->assertInternalType('array', $result);
    }

    public function testget_statuses()
    {
        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('counter')->will($this->returnValue(array()));

        $di = new \Box_Di();
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->get_statuses(array());
        $this->assertInternalType('array', $result);
    }

    public function testgroup_get_pairs()
    {
        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('getGroupPairs')->will($this->returnValue(array()));

        $di = new \Box_Di();
        $di['mod_service'] = $di->protect(function ($name) use($serviceMock) {return $serviceMock;});

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->group_get_pairs(array());
        $this->assertInternalType('array', $result);
    }

    public function testgroup_create()
    {
        $data['title'] = 'test Group';

        $newGroupId = 1;
        $serviceMock = $this->getMockBuilder('\Box\Mod\Client\Service')->getMock();
        $serviceMock->expects($this->atLeastOnce())->
            method('createGroup')
            ->will($this->returnValue($newGroupId));

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setService($serviceMock);

        $result = $admin_Client->group_create($data);

        $this->assertInternalType('int', $result);
        $this->assertEquals($newGroupId, $result);
    }

    public function testgroup_createMisssingTitle()
    {
        $data['title'] = null;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $this->setExpectedException('\Box_Exception', 'Group title is missing');
        $admin_Client->group_create($data);

    }

    public function testgroup_update()
    {
        $data['id'] = '2';
        $data['title'] = 'test Group updated';

        $model = new \Model_ClientGroup();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $dbMock->expects($this->atLeastOnce())
            ->method('store')->will($this->returnValue(1));


        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->group_update($data);

        $this->assertTrue($result);
    }

    public function testgroup_updateMissingId()
    {
        $data['id'] = null;

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $this->setExpectedException('\Box_Exception', 'Group id is missing');
        $admin_Client->group_update($data);
    }

    public function testgroup_updateGroupNotFound()
    {
        $data['id'] = '2';
        $data['title'] = 'test Group updated';

        $model = new \Model_ClientGroup();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(null));

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Group not found');
        $admin_Client->group_update($data);
    }

    public function testgroup_delete()
    {
        $data['id'] = '2';

        $model = new \Model_ClientGroup();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $serviceMock = $this->getMockBuilder('\Box\Client\Service')
            ->setMethods(array('deleteGroup'))
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('deleteGroup')
            ->will($this->returnValue(true));

        $di = new \Box_Di();
        $di['db'] = $dbMock;
        $di['logger'] = new \Box_Log();

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);
        $admin_Client->setService($serviceMock);

        $result = $admin_Client->group_delete($data);

        $this->assertTrue($result);
    }

    public function testgroup_deleteMissingId()
    {
        $data['id'] = null;

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $this->setExpectedException('\Box_Exception', 'Group id is missing');
        $admin_Client->group_delete($data);
    }

    public function testgroup_deleteGroupNotFound()
    {
        $data['id'] = '2';

        $model = new \Model_ClientGroup();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(null));

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Group not found');
        $admin_Client->group_delete($data);
    }

    public function testgroup_get()
    {
        $data['id'] = '2';

        $model = new \Model_ClientGroup();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue($model));

        $dbMock->expects($this->atLeastOnce())
            ->method('toArray')->will($this->returnValue(array()));

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $result = $admin_Client->group_get($data);

        $this->assertInternalType('array', $result);
    }

    public function testgroup_getMissingId()
    {
        $data['id'] = null;

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $this->setExpectedException('\Box_Exception', 'Group id is missing');
        $admin_Client->group_get($data);
    }

    public function testgroup_getGroupNotFound()
    {
        $data['id'] = '2';

        $model = new \Model_ClientGroup();
        $model->loadBean(new \RedBeanPHP\OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')->will($this->returnValue(null));

        $di = new \Box_Di();
        $di['db'] = $dbMock;

        $admin_Client = new \Box\Mod\Client\Api\Admin();
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Group not found');
        $admin_Client->group_get($data);
    }

    public function testlogin_history_delete()
    {
        $validatorMock = $this->getMockBuilder('\Box_Validate')->getMock();
        $validatorMock->expects($this->atLeastOnce())
            ->method('checkRequiredParamsForArray');
        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getExistingModelById')
            ->will($this->returnValue(new \Model_ActivityClientHistory()));
        $dbMock->expects($this->atLeastOnce())
            ->method('trash');

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $di = new \Box_Di();
        $di['validator'] = $validatorMock;
        $di['db'] = $dbMock;
        $admin_Client->setDi($di);

        $data = array('id' => 1);
        $result = $admin_Client->login_history_delete($data);
        $this->assertTrue($result);
    }

    public function testlogin_history_delete_NotFound()
    {
        $validatorMock = $this->getMockBuilder('\Box_Validate')->getMock();
        $validatorMock->expects($this->atLeastOnce())
            ->method('checkRequiredParamsForArray');
        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getExistingModelById')
            ->will($this->returnValue(null));

        $admin_Client = new \Box\Mod\Client\Api\Admin();

        $di = new \Box_Di();
        $di['validator'] = $validatorMock;
        $di['db'] = $dbMock;
        $admin_Client->setDi($di);

        $this->setExpectedException('\Box_Exception', 'Event not found');
        $data = array('id' => 1);
        $admin_Client->login_history_delete($data);
    }

    public function testBatch_delete()
    {
        $activityMock = $this->getMockBuilder('\Box\Mod\Client\Api\Admin')->setMethods(array('delete'))->getMock();
        $activityMock->expects($this->atLeastOnce())->method('delete')->will($this->returnValue(true));

        $validatorMock = $this->getMockBuilder('\Box_Validate')->disableOriginalConstructor()->getMock();
        $validatorMock->expects($this->atLeastOnce())
            ->method('checkRequiredParamsForArray')
            ->will($this->returnValue(null));

        $di              = new \Box_Di();
        $di['validator'] = $validatorMock;
        $activityMock->setDi($di);

        $result = $activityMock->batch_delete(array('ids' => array(1, 2, 3)));
        $this->assertEquals(true, $result);
    }

    public function testBatch_delete_log()
    {
        $activityMock = $this->getMockBuilder('\Box\Mod\Client\Api\Admin')->setMethods(array('login_history_delete'))->getMock();
        $activityMock->expects($this->atLeastOnce())->method('login_history_delete')->will($this->returnValue(true));

        $validatorMock = $this->getMockBuilder('\Box_Validate')->disableOriginalConstructor()->getMock();
        $validatorMock->expects($this->atLeastOnce())
            ->method('checkRequiredParamsForArray')
            ->will($this->returnValue(null));

        $di              = new \Box_Di();
        $di['validator'] = $validatorMock;
        $activityMock->setDi($di);

        $result = $activityMock->batch_delete_log(array('ids' => array(1, 2, 3)));
        $this->assertEquals(true, $result);
    }


}
