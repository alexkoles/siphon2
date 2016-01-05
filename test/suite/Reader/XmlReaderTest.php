<?php

namespace Icecave\Siphon\Reader;

use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\Response;
use Clue\React\Buzz\Message\ResponseException;
use Eloquent\Phony\Phpunit\Phony;
use Exception;
use Icecave\Chrono\DateTime;
use Icecave\Siphon\Reader\Exception\NotFoundException;
use Icecave\Siphon\Reader\Exception\ServiceUnavailableException;
use PHPUnit_Framework_TestCase;
use React\Promise;
use SimpleXMLElement;

class XmlReaderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->httpClient = Phony::mock(Browser::class);
        $this->response   = Phony::mock(Response::class);

        $this->httpClient->get->returns(Promise\resolve($this->response->mock()));
        $this->response->getBody->returns('<xml></xml>');

        $this->reader = new XmlReader($this->httpClient->mock());

        $this->resolve = Phony::spy();
        $this->reject = Phony::spy();
    }

    public function testRead()
    {
        $this->reader->read('url')->done($this->resolve, $this->reject);

        $this->httpClient->get->calledWith('url');
        $this->resolve->calledWith(
            [
                new SimpleXMLElement('<xml></xml>', LIBXML_NONET),
                null,
            ]
        );
        $this->reject->never()->called();
    }

    public function testReadWithLastModified()
    {
        $this->response->getHeader->with('Last-Modified')->returns('Wed, 13 May 2015 17:37:44 GMT');

        $this->reader->read('url')->done($this->resolve, $this->reject);

        $this->resolve->calledWith(
            [
                new SimpleXMLElement('<xml></xml>', LIBXML_NONET),
                new DateTime(2015, 05, 13, 17, 37, 44),
            ]
        );
    }

    public function testReadWithHttpClientException()
    {
        $exception = new ResponseException($this->response->mock());
        $this->httpClient->get->returns(Promise\reject($exception));
        $this->reader->read('url')->done($this->resolve, $this->reject);

        $this->reject->calledWith(new ServiceUnavailableException($exception));
        $this->resolve->never()->called();
    }

    public function testReadWithNotFoundException()
    {
        $exception = new ResponseException($this->response->mock());
        $this->response->getCode->returns(404);
        $this->httpClient->get->returns(Promise\reject($exception));
        $this->reader->read('url')->done($this->resolve, $this->reject);

        $this->reject->calledWith(new NotFoundException($exception));
        $this->resolve->never()->called();
    }

    public function testReadWithGenericException()
    {
        $exception = new Exception('The exception!');
        $this->httpClient->get->returns(Promise\reject($exception));
        $this->reader->read('url')->done($this->resolve, $this->reject);

        $this->reject->calledWith(new ServiceUnavailableException($exception));
        $this->resolve->never()->called();
    }

    public function testXmlParseErrorWithServiceUnavailableException()
    {
        $this->response->getBody->returns('');
        $this->reader->read('url')->done($this->resolve, $this->reject);

        $this->reject->calledWith($this->isInstanceOf(ServiceUnavailableException::class));
        $this->resolve->never()->called();
    }
}
