<?php

namespace Tests\Unit;

use MessageBird\Objects\SignedRequest;
use MessageBird\RequestValidator;
use PHPUnit\Framework\TestCase;

class RequestValidatorTest extends TestCase
{
    public function testVerify()
    {
        $query = [
            'recipient' => '31612345678',
            'reference' => 'FOO',
            'statusDatetime' => '2019-01-11T09:17:11+00:00',
            'id' => 'eef0ab57a9e049be946f3821568c2b2e',
            'status' => 'delivered',
            'mccmnc' => '20408',
            'ported' => '1',
        ];
        $signature = 'KVBdcVdz2lYMwcBLZCRITgxUfA/WkwSi+T3Wxl2HL6w=';
        $requestTimestamp = 1547198231;
        $body = '';

        $request = SignedRequest::create($query, $signature, $requestTimestamp, $body);
        $validator = new RequestValidator('PlLrKaqvZNRR5zAjm42ZT6q1SQxgbbGd');

        $this->assertTrue($validator->verify($request));
    }

    public function testVerifyWithBody()
    {
        $query = [
            'recipient' => '31612345678',
            'reference' => 'FOO',
            'statusDatetime' => '2019-01-11T09:17:11+00:00',
            'id' => 'eef0ab57a9e049be946f3821568c2b2e',
            'status' => 'delivered',
            'mccmnc' => '20408',
            'ported' => '1',
        ];
        $signature = '2bl+38H4oHVg03pC3bk2LvCB0IHFgfC4cL5HPQ0LdmI=';
        $requestTimestamp = 1547198231;
        $body = '{"foo":"bar"}';

        $request = SignedRequest::create($query, $signature, $requestTimestamp, $body);
        $validator = new RequestValidator('PlLrKaqvZNRR5zAjm42ZT6q1SQxgbbGd');

        $this->assertTrue($validator->verify($request));
    }

    public function testVerificationFails()
    {
        $query = [
            'recipient' => '31612345678',
            'reference' => 'BAR',
            'statusDatetime' => '2019-01-11T09:17:11+00:00',
            'id' => 'eef0ab57a9e049be946f3821568c2b2e',
            'status' => 'delivered',
            'mccmnc' => '20408',
            'ported' => '1',
        ];
        $signature = 'KVBdcVdz2lYMwcBLZCRITgxUfA/WkwSi+T3Wxl2HL6w=';
        $requestTimestamp = 1547198231;
        $body = '';

        $request = SignedRequest::create($query, $signature, $requestTimestamp, $body);
        $validator = new RequestValidator('PlLrKaqvZNRR5zAjm42ZT6q1SQxgbbGd');

        $this->assertFalse($validator->verify($request));
    }

    public function testRecentRequest()
    {
        $query = [];
        $signature = 'KVBdcVdz2lYMwcBLZCRITgxUfA/WkwSi+T3Wxl2HL6w=';
        $requestTimestamp = time() - 1;
        $body = '';

        $request = SignedRequest::create($query, $signature, $requestTimestamp, $body);
        $validator = new RequestValidator('');

        $this->assertTrue($validator->isRecent($request));
    }

    public function testExpiredRequest()
    {
        $query = [];
        $signature = 'KVBdcVdz2lYMwcBLZCRITgxUfA/WkwSi+T3Wxl2HL6w=';
        $requestTimestamp = time() - 100;
        $body = '';

        $request = SignedRequest::create($query, $signature, $requestTimestamp, $body);
        $validator = new RequestValidator('');

        $this->assertFalse($validator->isRecent($request));
    }
}
