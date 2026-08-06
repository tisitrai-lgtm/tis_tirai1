<?php
namespace XBase;

class Table {
    protected $fp;
    public $recordCount;
    public $headerLength;
    public $recordLength;
    public $columns = [];

    public function __construct($file) {
        if (!file_exists($file)) throw new \Exception("ไม่พบไฟล์: " . $file);
        $this->fp = fopen($file, 'rb');
        $this->readHeader();
    }

    protected function readHeader() {
        fseek($this->fp, 4);
        $this->recordCount = unpack('L', fread($this->fp, 4))[1];
        $this->headerLength = unpack('v', fread($this->fp, 2))[1];
        $this->recordLength = unpack('v', fread($this->fp, 2))[1];

        fseek($this->fp, 32);
        while (ftell($this->fp) < $this->headerLength - 1) {
            $buf = fread($this->fp, 32);
            if (ord($buf[0]) == 0x0D) break;
            
            $this->columns[] = [
                'name'   => trim(str_replace(chr(0), '', substr($buf, 0, 11))),
                'type'   => chr(ord($buf[11])),
                'length' => ord($buf[16])
            ];
        }
    }

    public function nextRecord() {
        static $current = 0;
        if ($current >= $this->recordCount) return false;
        
        $pos = $this->headerLength + ($current * $this->recordLength);
        fseek($this->fp, $pos);
        $buf = fread($this->fp, $this->recordLength);
        $current++;
        
        return new Record($buf, $this->columns);
    }

    public function close() { if ($this->fp) fclose($this->fp); }
}

class Record {
    protected $data;
    protected $columns;

    public function __construct($data, $columns) {
        $this->data = $data;
        $this->columns = $columns;
    }

    // ดึงข้อมูลตามลำดับที่ (Index)
    public function getByIndex($index) {
        $offset = 1; // ข้าม Deletion Flag
        for ($i = 0; $i < count($this->columns); $i++) {
            if ($i === $index) {
                return trim(substr($this->data, $offset, $this->columns[$i]['length']));
            }
            $offset += $this->columns[$i]['length'];
        }
        return '';
    }

    // ดึงตามชื่อ (ใช้สำหรับการตรวจสอบชื่อฟิลด์)
    public function get($columnName) {
        $offset = 1;
        foreach ($this->columns as $col) {
            if (strcasecmp($col['name'], $columnName) === 0) {
                return trim(substr($this->data, $offset, $col['length']));
            }
            $offset += $col['length'];
        }
        return '';
    }
}