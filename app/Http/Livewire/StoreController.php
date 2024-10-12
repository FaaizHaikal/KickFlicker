<?php

namespace App\Http\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Exception;

class StoreController extends Component
{

        use WithFileUploads;

        public $user;
        #[Locked]
        public $isHaveStore = null;
        #[Validate('required')]
        public $store_name = null;
        public $products = [];

        // New product fields
        public $product_name = null;
        public $product_description = null;
        public $product_image = null;
        public $product_stock = null;
        public $product_price = null;

        // form button
        public $isFormHidden = true;

        public function setFormHidden()
        {
            if( $this->isFormHidden == true) {
                $this->isFormHidden = false;
            } else {
                $this->isFormHidden = true;
            }
            Log::info($this->isFormHidden);
        }
    

        public function mount()
        {
            // Fetch the latest user data from the database
            $this->user = User::find(Auth::id());
            if($this->user->store_name != null) {
                $this->isHaveStore = true;
                $this->store_name = $this->user->store_name;
                $this->products = Product::where('user_id', $this->user->id)->get();
            } 
            else {
                Log::info("dont have duh");
                $this->isHaveStore = false; // Set to false if the user doesn't have a store
            }
        }

        public function AddStore(Request $request)
        {
            $this->user = User::find(Auth::id());

            if ($this->user && $this->user->store_name == null) {
                $this->user->store_name = $this->store_name;
                $this->user->save();
            }

            return redirect('/mystore');
        }

        public function AddProduct(Request $request)
        {
            $this->user = User::find(Auth::id());
            
            try {
                $imagePath = $this->product_image->store('products', 'public'); 
            } catch (Exception $e) {
                Log::info($e);
            }

            try {
                $newProduct = Product::create([
                    'name' => $this->product_name,
                    'description' => $this->product_description,
                    'image' => $imagePath,
                    'stock' => $this->product_stock,
                    'price' => $this->product_price,
                    'user_id' => $this->user->id,
                ]);

                $this->reset('product_name', 'product_description', 'product_image', 'product_stock', 'product_price');

                return redirect('/mystore');
            } catch (Exception $e) {
                Log::info($e);
            }
        }

        public function RemoveProduct($productId)
        {
            $product = Product::find($productId);
            if ($product) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $product->delete();
                $this->products = Product::all();
            } 

        }


        public function render()
        {
            $user = User::find(Auth::id());

            $userId = $user->id;
            $username = $user->username;
            $email = $user->email;
            $store_name = $user->store_name;
            $this->products = Product::where('user_id', $userId)->get();

            return view('store', compact('userId', 'username', 'email', 'store_name'))->layout('layouts.app');
        }
}
