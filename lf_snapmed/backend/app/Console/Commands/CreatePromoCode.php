<?php

namespace App\Console\Commands;

use App\PromoCode;
use Illuminate\Console\Command;

class CreatePromoCode extends Command
{
    const CURRENCIES = 'USD EUR NOK';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promo:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To create a new promo code in the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $promo = new PromoCode();
        $oneTimeCode = false;
        $fillables = $promo->getFillable();
        foreach ($fillables as $key => $fillable) {
            if ($fillable == 'valid_from' || $fillable == 'valid_to') {
                $input = $this->ask(($key+1) . "/" . count($fillables) . " $fillable: Enter as DD-MM-YYYY (Not Mandatory)");
                if ($input){
                $promo->$fillable = date("Y-m-d H:i:s", strtotime($input));
                }
            }
            // There should be a better way to do this. For now, string would work instead of creating new table relation or column per currency
            elseif ($fillable == 'applicable_currencies') {
                $input = strtoupper($this->ask(($key+1) . "/" . count($fillables) . " $fillable: 'ALL' for all currencies Otherwise '<currency1> <currency2> <currency3>'"));
                $promo->$fillable = $input;
                // if ($input == 'ALL'){
                //     $promo->$fillable = self::CURRENCIES;
                // }
                // else{
                //     $promo->$fillable = $input;
                // }
            }
            elseif (substr($fillable, 0, strlen('discount_')) == 'discount_'){
                if ($fillable == 'discount_fixed'){
                    $input = $this->ask(($key+1) . "/" . count($fillables) . " $fillable (yes/no) ['yes' for fixed discount/ 'no' for percentage discount]");
                    if ($input == 'yes'){
                        $promo->$fillable = 1;
                    }
                }
                else{
                    $input = $this->ask(($key+1) . "/" . count($fillables) . " $fillable (Enter as decimal XX.XX)");
                    if ($input){
                        $promo->$fillable = $input;
                    }
                    else{
                        $promo->$fillable = 0.00;
                    }
                }
            }

            elseif ($fillable == 'one_time_code'){
                $input =  $this->ask(($key+1) . "/" . count($fillables) . " $fillable (yes/no)");
                if ($input == 'yes'){
                    $promo->$fillable = 1;
                    $oneTimeCode = true;
                }
            }

            elseif($fillable == 'reusable'){
                $input =  $this->ask(($key+1) . "/" . count($fillables) . " $fillable (yes/no). This will default to 'no' for one time codes.");

                if ($oneTimeCode || $input == 'no'){
                    $promo->$fillable = 0;
                }
            }
            elseif($fillable == 'promocode'){
                $promo->$fillable = strtoupper($this->ask(($key+1) . "/" . count($fillables) . " $fillable"));
            }
            else {
                $promo->$fillable = $this->ask(($key+1) . "/" . count($fillables) . " $fillable");
            }
        }
        if ($this->confirm("Do you want to create this promo code?", true)) {
            $promo->save();

            $this->info("Promo code created (uuid: {$promo->uuid})");
        }
        return true;
    }
}
