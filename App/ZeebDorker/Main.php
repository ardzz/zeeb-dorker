<?php

/**
 * @author Ardhana <ardzz@indoxploit.or.id>
 * 
 * 
 * Big Thanks for Teguh Aprianto about the Google CSE Key! and Izzeldin Addarda [ Security Ghost ]
 */
namespace ZeebDorker;

class Main implements DorkerInterface{

    /**
     * @var string
     */
    private $CSE_TOKEN = "partner-pub-2698861478625135:3033704849";

    function __construct(){
        $this->request = new \HttpRequest\Main;
    }

    /**
     * @param string $str String
     * @param string $find_start Mencari string pertama
     * @param string $find_end Mencari string terakhir
     * @return string
     */
    function getString($str, $find_start, $find_end) {
		$start = @strpos($str,$find_start);
		if (!$start) {
			return false;
		}
		$length = strlen($find_start);
		$end    = strpos(substr($str,$start +$length),$find_end);
		return trim(substr($str,$start +$length,$end));
	}

    /**
     * @param string $dork Setting dork
     * @return object $this
     */
    function setDork(String $dork){
        $this->dork = (String) $dork;
        return $this;
    }

    /**
     * @param array $array
     * @return object
     */
    function convert2object(Array $array){
        return json_decode(json_encode($array));
    }

    /**
     * @method getInfo() untuk mengumpulkan informasi sebelum melakukan 'searching' di mesin CSE Google
     * @return boolean
     */
    function getInfo(){
        $this->headers = [
            "Referer: https://cse.google.com/cse?cx=" . $this->CSE_TOKEN,
        ];

        $this->request->isGET();
        $this->request->url = "https://cse.google.com/cse.js?" . http_build_query(["hpg" => "1", "cx" => $this->CSE_TOKEN]);
        $this->request->headers = $this->headers;
        $this->request->randomAgent();

        // Debug
        // $this->request->proxy = "127.0.0.1:8080";
        
        $this->request->execute();
        $body = $this->request->getBody();

        $start = base64_decode("fSko");
        $stop  = base64_decode("KTs=");
        $data = $this->getString($body, $start, $stop);
        
        if ($data) {
            $this->data = json_decode($data);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @method parseData() Untuk memparsing data setelah menerima informasi seperti cse_token, namun belum dikonversi agar bisa dipakai di @method search()
     * @see self::getInfo()
     * @return boolean
     */
    function parseData(){
        /**
         * 
         * cselibVersion -> CSE Library Version
         * cx
         * cse_token -> CSE Token
         * exp ["csqr", value]
         * 
         */
        if ($this->getInfo()) {
            $data = [
                "cse_lib_version" => $this->data->cselibVersion,
                "cx" => $this->data->cx,
                "cse_token" => $this->data->cse_token,
                "exp" => implode(",", $this->data->exp)
            ];
            $this->data = $this->convert2object($data);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @method search() Adalah method untuk melakukan pencarian di CSE Google
     * @param string|integer $start Adalah urutan proses pencarian
     * @example
     * $start = 40;
     * Berarti urutan proses pencarian adalah 4, setiap selesai melakukan pencarian maka nilai @var $start akan ditambah 10
     * @see self::parseData()
     * @return boolean
     */
    function search($start = 0){
        if ( isset($this->dork) && !empty($this->dork) && !is_array($this->dork) && $this->parseData() ) {
            $query = [ 
                "rsz"      => "filtered_cse",
                "num"      => 10,
                "start"    => $start,
                "hl"       => "en",
                "source"   => "gcsc",
                "gss"      => ".com",
                "cselibv"  => $this->data->cse_lib_version,
                "cx"       => $this->data->cx,
                "q"        => $this->dork,                    // Dork atau kueri pencarian
                "safe"     => "off",                          // Safe mode = off
                "cse_tok"  => $this->data->cse_token,         // CSE Token yang didapat dari method getInfo()
                "exp"      => $this->data->exp,
                "callback" => "google.search.cse.api16950"
            ];
            $this->request->isGET();
            $this->request->url = "https://cse.google.com/cse/element/v1?" . http_build_query($query);
            $this->request->headers = $this->headers;
            $this->request->randomAgent();

            // Debug
            // $this->request->proxy = "127.0.0.1:8080";

            $this->request->execute();
            $body  = $this->request->getBody();

            $hash  = hash("sha256", rand(000, 999));
            $start = base64_decode("Z29vZ2xlLnNlYXJjaC5jc2UuYXBpMTY5NTAo");
            $stop  = base64_decode("KTs=");

            $output = $this->getString($body . $hash, $start, $stop . $hash);
            if (stripos($output, "clicktrackUrl")) {
                $this->output = json_decode($output, 1);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @method parseOutput Adalah method yang akan mem-parsing data hasil dari @method search()
     * @see self::search()
     * @return array
     */
    function parseOutput(){
        if (isset($this->output) && !empty($this->output) && is_array($this->output)) {
            $data = $this->output["results"];
            $output = [];
            $x = 1;
            foreach ($data as $key => $value) {
                $loop = $x++;
                $output[$loop]["url"] = $value["unescapedUrl"];
                $output[$loop]["cache"] = (isset($value["cacheUrl"]) ? $value["cacheUrl"] : "N/A");
                $output[$loop]["title"] = $value["titleNoFormatting"];
                $output[$loop]["content"] = $value["contentNoFormatting"];
                $output[$loop]["thumnail"] = (isset($value["richSnippet"]["cseThumbnail"]["src"]) ? $value["richSnippet"]["cseThumbnail"]["src"] : "N/A");
            }
            return $output;
        } else {
            return false;
        }  
    }

    /**
     * @method write Untuk menulis file, method ini tidak men-overwrite atau mereplace file yang sudah ada
     * @param string $name Adalah nama file yang akan disimpan
     * @param string|integer $source Adalah source atau isi file yang akan dismpan
     * @example
     * self::write("logs.txt", "ZeebDorker - Logs");
     * @return boolean
     */
    function write(String $name, $source){
        $fopen = @fopen($name, "a");
        $output = @fwrite($fopen, $source);
        @fclose($fopen);
        return (boolean) str_replace(0, 1, $output);
    }

    /**
     * @method cls Untuk membersihkan screen terminal
     */
    function cls(){
		echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
	}
}
?>