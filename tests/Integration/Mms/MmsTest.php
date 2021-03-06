<?php

namespace Tests\Integration\Mms;

use Tests\Integration\BaseTest;

class MmsTest extends BaseTest
{
    public function testCreateMmsFail()
    {
        $this->expectException(\MessageBird\Exceptions\RequestException::class);
        $mmsMessage = new \MessageBird\Objects\MmsMessage();

        $this->mockClient->expects($this->once())
            ->method('performHttpRequest')
            ->with('POST', 'mms', null, json_encode($mmsMessage))
            ->willReturn([422, '', '{"errors":[{"code":9,"description":"no (correct) recipients found","parameter":"recipients"}]}']);

        $this->client->mmsMessages->create($mmsMessage);
    }

    public function testCreateMmsSuccess()
    {
        $mmsMessage = $this->generateDummyMessage();
        $dummyMessageId = 'message_id';

        $this->mockClient->expects($this->once())
            ->method('performHttpRequest')
            ->with('POST', 'mms', null, json_encode($mmsMessage))
            ->willReturn([200, '', $this->generateMmsServerResponse($mmsMessage, $dummyMessageId)]);

        $resultMmsMessage = $this->client->mmsMessages->create($mmsMessage);

        $this->assertMessagesAreEqual($mmsMessage, $resultMmsMessage, $dummyMessageId);
    }

    public function testListMms()
    {
        $dummyMessage = $this->generateDummyMessage();

        $this->mockClient->expects($this->once())
            ->method('performHttpRequest')
            ->with('GET', 'mms', ['offset' => '100', 'limit' => '30'], null)
            ->willReturn([200, '',
                '{
                    "offset": 0, "limit": 20, "count": 1, "totalCount": 2,
                    "links": {
                        "first": "https://rest.messagebird.com/mms/?offset=0&type=mms",
                        "previous": null,
                        "next": null,
                        "last": "https://rest.messagebird.com/mms/?offset=0&type=mms"
                    },
                    "items": [ ' . $this->generateMmsServerResponse($dummyMessage, 'message_id') . ',
                               ' . $this->generateMmsServerResponse($dummyMessage, 'message_id_2') . ']
                }'
            ]);

        $resultMessages = $this->client->mmsMessages->getList(['offset' => 100, 'limit' => 30]);

        $this->assertSame(0, $resultMessages->offset);
        $this->assertSame(1, $resultMessages->count);
        $this->assertSame(2, $resultMessages->totalCount);

        $this->assertCount(2, $resultMessages->items);
        $this->assertMessagesAreEqual($dummyMessage, $resultMessages->items[0], 'message_id');
        $this->assertMessagesAreEqual($dummyMessage, $resultMessages->items[1], 'message_id_2');
    }

    public function testDeleteMms()
    {
        $this->expectException(\MessageBird\Exceptions\RequestException::class);
        $this->mockClient->expects($this->exactly(2))
            ->method('performHttpRequest')
            ->with('DELETE', 'mms/message_id', null, null)
            ->will($this->onConsecutiveCalls(
                [204, '', ''],
                [404, '', '{"errors":[{"code":20,"description":"message not found","parameter":null}]}']
            ));

        $this->client->mmsMessages->delete('message_id');

        // Must throw \MessageBird\Exceptions\RequestException
        $this->client->mmsMessages->delete('message_id');
    }

    public function testReadMms()
    {
        $this->expectException(\MessageBird\Exceptions\RequestException::class);
        $dummyMessage = $this->generateDummyMessage();

        $this->mockClient->expects($this->exactly(2))
            ->method('performHttpRequest')
            ->with('GET', $this->logicalOr('mms/message_id', 'mms/unknown_message_id'), null, null)
            ->will($this->onConsecutiveCalls(
                ['200', '', $this->generateMmsServerResponse($dummyMessage, 'message_id')],
                ['404', '', '{"errors":[{"code":20,"description":"message not found","parameter":null}]}']
            ));

        $resultMmsMessage = $this->client->mmsMessages->read('message_id');
        $this->assertMessagesAreEqual($dummyMessage, $resultMmsMessage, 'message_id');

        // Must throw \MessageBird\Exceptions\RequestException
        $this->client->mmsMessages->read('unknown_message_id');
    }

    /**
     * @return \MessageBird\Objects\MmsMessage
     */
    private function generateDummyMessage()
    {
        $mmsMessage = new \MessageBird\Objects\MmsMessage();
        $mmsMessage->originator = "MessageBird";
        $mmsMessage->direction = 'ot';
        $mmsMessage->recipients = [31621938645];
        $mmsMessage->body = 'Have you seen this logo?';
        $mmsMessage->mediaUrls = ['https://www.messagebird.com/assets/images/og/messagebird.gif'];
        return $mmsMessage;
    }

    /**
     * Asserts if 2 messages are equal. They are equal if all the attributes have the same value.
     * @param \MessageBird\Objects\MmsMessage $mmsMessage
     * @param \MessageBird\Objects\MmsMessage $resultMmsMessage
     * @param $expectedId Since the id field cannot be manually set this the id of the message is checked against this expectedId value.
     */
    private function assertMessagesAreEqual(\MessageBird\Objects\MmsMessage $mmsMessage, \MessageBird\Objects\MmsMessage $resultMmsMessage, $expectedId)
    {
        $this->assertSame($expectedId, $resultMmsMessage->getId());
        $this->assertSame("https://rest.messagebird.com/mms/{$expectedId}", $resultMmsMessage->getHref());
        $this->assertSame($mmsMessage->direction, $resultMmsMessage->direction);
        $this->assertSame($mmsMessage->originator, $resultMmsMessage->originator);
        $this->assertSame($mmsMessage->subject, $resultMmsMessage->subject);
        $this->assertSame($mmsMessage->body, $resultMmsMessage->body);
        $this->assertSame($mmsMessage->mediaUrls, $resultMmsMessage->mediaUrls);
        $this->assertSame($mmsMessage->reference, $resultMmsMessage->reference);

        foreach ($resultMmsMessage->recipients->items as $item) {
            $this->assertContains($item->recipient, $mmsMessage->recipients);
        }
    }

    /**
     * @return string JSON string containing the generated server response.
     */
    private function generateMmsServerResponse(\MessageBird\Objects\MmsMessage $mmsMessage, $messageId)
    {
        return '{
            "id": "' . $messageId . '",
            "href": "https://rest.messagebird.com/mms/' . $messageId . '",
            "direction": ' . json_encode($mmsMessage->direction) . ',
            "originator": ' . json_encode($mmsMessage->originator) . ',
            "subject": ' . json_encode($mmsMessage->subject) . ',
            "body": ' . json_encode($mmsMessage->body) . ',
            "mediaUrls": ' . json_encode($mmsMessage->mediaUrls) . ',
            "reference": ' . json_encode($mmsMessage->reference) . ',
            "scheduledDatetime": ' . json_encode($mmsMessage->scheduledDatetime) . ',
            "createdDatetime": "2017-09-19T15:08:46+00:00",
            "recipients": {
                "totalCount": 1,
                "totalSentCount": 1,
                "totalDeliveredCount": 0,
                "totalDeliveryFailedCount": 0,
                "items": [
                    {
                        "recipient": ' . json_encode($mmsMessage->recipients[0]) . ',
                        "status": "sent",
                        "statusDatetime": "2017-09-19T15:08:46+00:00"
                    }
                ]
            }
        }';
    }
}
