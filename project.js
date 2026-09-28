'use strict'

import express from "express";
import { error } from "node:console";
import path from 'path'
import { fileURLToPath } from 'url'

import multer from "multer"
import FormData from 'form-data';
import fs from 'fs'


const JWT_SECRET = '250050044'
const app = express();

const PORT = 3001

//AI service image file
const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        cb(null, 'public/uploads/'); // Make sure this folder exists in your project directory
    },
    filename: function (req, file, cb) {
        // Keeps the original extension (e.g., .png, .jpg) and adds a timestamp to avoid duplicates
        const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
        cb(null, file.fieldname + '-' + uniqueSuffix + path.extname(file.originalname));
    }
});

//voice input
const uploadaudio = multer({ storage: multer.memoryStorage() });

const upload = multer({ storage: storage });

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.set('view engine', 'ejs');

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
app.use(express.static(path.join(__dirname, 'public')))

let user = {
    _id:"",
    username:"",
    password:"",
    email:"",
    cart:[],
    role:"guest"
}
//AI voice page


//AI page for up load image
app.get("/computevision", async(req,res)=>{
    try{

        res.render("vision.ejs",{message:"",data:"---", userData: user, productData:"",ad:""})
    }catch (error) {

        console.error("服务器内部错误:", error);
        res.status(500).json({ error: "fail to get the ai page" });
    }
    
})

app.post("/upload-endpoint",upload.single('image'),async(req,res)=>{
    try{
        if (!req.file) return res.status(400).send('沒有收到圖片');

        //AI
        const localImagePath = req.file.path;


        const url = `http://localhost:1000/aivision`
        const options = {
            method:"POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({ 
                img: localImagePath 
            })
        }
        const rawData = await fetch(url, options)
        if (rawData.status !== 200) throw `Failed to find photo`;
        const jsonData = await rawData.json();
        console.log(`${localImagePath} scan successful`)


        //audio

        const speechurl = `http://localhost:1000/speech`;
            const speechoptions = {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    waring: jsonData.textList.waring,
                    mname: jsonData.textList.mname,
                    muse: jsonData.textList.muse,
                    mnotice: jsonData.textList.mnotice,
                    mdate: jsonData.textList.mdate
                })
        };

        const speechrawData = await fetch(speechurl, speechoptions);
        if (speechrawData.status !== 200) throw `speech error`;
        const audioBlob = await speechrawData.blob();
        const arrayBuffer = await audioBlob.arrayBuffer();
        const buffer = Buffer.from(arrayBuffer);
        const base64Audio = buffer.toString('base64');
        



        //Open AI
        const openurl = `http://localhost:1000/open/${jsonData.textList.mname}`
        const openrawData = await fetch(openurl)
        const openjsonData = await openrawData.json()

        console.log(openjsonData.output_text)

        //Get Products
        const url1 = `http://localhost:10888/product/get/${openjsonData.output_text}`
        const rawData1 = await fetch(url1);
        if (rawData1.status !== 200) throw `Failed to retrieve products`;
        const jsonData1 = await rawData1.json();

        //add to action
        if(user.role === "guest"){
            const actionurl = `http://localhost:10888/post/action`
            const actionoptions = {
                method: "POST",
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    _id: user._id,
                    role: user.role,
                    action:"/upload-endpoint",
                    method:"POST",
                    output:openjsonData.output_text
                })
            }
            const actionrawData = await fetch(actionurl, actionoptions)
            const actionjsonData = await actionrawData.json();
            console.log(actionjsonData)
        }else{
            const actionurl = `http://localhost:10888/post/action`
            const actionoptions = {
                method: "POST",
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    _id: user._id,
                    role: user.role,
                    action:"/upload-endpoint",
                    method:"POST",
                    output:openjsonData.output_text
                })
            }
            const actionrawData = await fetch(actionurl, actionoptions)
            const actionjsonData = await actionrawData.json();
            console.log(actionjsonData)
        }

        
        res.render('vision.ejs', { message: '分析成功！', data: jsonData.textList , userData: user, productData: jsonData1,ad:base64Audio})
    } catch (error) {
        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/upload-endpoint",
                method:"POST",
                output:`Error: ${error}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
        console.error("服务器内部错误:", error);
        res.status(500).json({ error: "分析图片时发生错误" });
    }
})

//main page
app.get('/', async(req,res) =>{
    console.log("Retrieving all products");
    try{
        const url = `http://localhost:10888/products/get`;


        const rawData = await fetch(url);
        if (rawData.status !== 200) throw `Failed to retrieve products`;
        const jsonData = await rawData.json(); // received and pass to next layer
        console.log(`${jsonData.length} products has been retrieved`);

        //audio
        const speechurl = `http://localhost:1000/speech/main`;

        const speechrawData = await fetch(speechurl);
        if (speechrawData.status !== 200) throw `speech error`;
        const audioBlob = await speechrawData.blob();
        const arrayBuffer = await audioBlob.arrayBuffer();
        const buffer = Buffer.from(arrayBuffer);
        const base64Audio = buffer.toString('base64');

        //action
        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/",
                method:"GET",
                output:"success"
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render('project.ejs',{productData:jsonData, userData:user, ad:base64Audio})
    } catch(err){

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/",
                method:"GET",
                output:"fail"
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        console.error(`Error to access the product ${err}`)
        res.status(500).send({ err: err.toString() });
    }
});

//search
app.get('/search', async(req,res)=>{
    console.log("seraching products");
    try{
        const searchKeyword = req.query.search || ""

        const url = `http://localhost:10888/search/get?search=${encodeURIComponent(searchKeyword)}`;
        console.log("Searching products with URL:", url);

        const rawData = await fetch(url);

        if (rawData.status !== 200) throw `Failed to retrieve products`;
        const jsonData = await rawData.json(); // received and pass to next layer
        console.log(`${jsonData.length} products has been retrieved`)

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/search",
                method:"GET",
                output:"success"
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render('search.ejs',{searchData:jsonData, userData:user})
    } catch (err) {

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/search",
                method:"GET",
                output:"fail"
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        console.error(`Error to access the product ${err}`);
    
        res.status(500).send({ err: err.message || err.toString() });
    }
})

//add product to cart
app.post('/cart/add', async(req,res)=>{
    const {type,mtype,name,brand,size, price, img, pid} = req.body
    const product = {
            "type": type,
            "mtype": mtype,
            "name": name,
            "brand": brand,
            "size": size,
            "price": price,
            "img": img,
            "pid": pid
    }
    if (user.role === 'guest') {
        user.cart.push(product);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/cart/add",
                method:"POST",
                output:product.name
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect('/cart');


    } else {
        const url = 'http://localhost:10888/user/cart/add';
        const options = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                product: product
            })
        };
        

        try {
            const rawData = await fetch(url, options);
            if (rawData.status !== 200) throw `Failed to add item "${name}"`;
            
            const jsonData = await rawData.json();
            console.log(`item "${name}" has been added`);

            const actionurl = `http://localhost:10888/post/action`
            const actionoptions = {
                method: "POST",
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    _id: user._id,
                    role: user.role,
                    action:"/cart/add",
                    method:"POST",
                    output:product.name
                })
            }
            const actionrawData = await fetch(actionurl, actionoptions)
            const actionjsonData = await actionrawData.json();
            console.log(actionjsonData)

            res.redirect('/cart')
        } catch (err) {
            console.error('Error to add product to cart:', err);

            const actionurl = `http://localhost:10888/post/action`
            const actionoptions = {
                method: "POST",
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    _id: user._id,
                    role: user.role,
                    action:"/cart/add",
                    method:"POST",
                    output:`fail to add ${product.name}`
                })
            }
            const actionrawData = await fetch(actionurl, actionoptions)
            const actionjsonData = await actionrawData.json();
            console.log(actionjsonData)
            
            res.status(500).send({ err: err.message || err.toString() });
        }
    }
})

//delete the item from the cart
app.get('/cart/delete', async(req,res)=>{
    try{
        const productPid = req.query.pid
        if (user.role === 'guest') {
            let indexproduct
            for(let i of user.cart){
                if(i.pid === productPid){
                    indexproduct = user.cart.indexOf(i) 
                }
            }
            user.cart.splice(indexproduct,1)

            const actionurl = `http://localhost:10888/post/action`
            const actionoptions = {
                method: "POST",
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    _id: user._id,
                    role: user.role,
                    action:"/cart/delete",
                    method:"PUT",
                    output:`deleted item ${productPid}`
                })
            }
            const actionrawData = await fetch(actionurl, actionoptions)
            const actionjsonData = await actionrawData.json();
            console.log(actionjsonData)

            res.redirect('/cart');
        }else{
            const userID = user._id
            const url = 'http://localhost:10888/user/cart/delete'
            const options = {
                method: "POST",
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    _id: userID,
                    pid: productPid
                })
            }
            const rawData = await fetch(url, options);
            if (rawData.status !== 200) throw `Failed to delete item "${productPid}"`;
            const jsonData = await rawData.json();
            console.log(`item has been deleted`);

            const actionurl = `http://localhost:10888/post/action`
            const actionoptions = {
                method: "POST",
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    _id: user._id,
                    role: user.role,
                    action:"/cart/delete",
                    method:"PUT",
                    output:`deleted item`
                })
            }
            const actionrawData = await fetch(actionurl, actionoptions)
            const actionjsonData = await actionrawData.json();
            console.log(actionjsonData)

            res.redirect('/cart')
        }

    }catch(err){
        console.error(`Error to add product to cart ${err}`)

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/cart/delete",
                method:"PUT",
                output:`fail to delete item`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.status(500).send({ err: err });
    }
})

//go to pay page
app.get('/cart',async(req,res) =>{
        let totalPrice = 0
        if (user.role === 'guest'){
            for(let i=0; i<user.cart.length; i++){
                totalPrice = totalPrice + Number(user.cart[i].price)
            }
            
            const actionurl = `http://localhost:10888/post/action`
            const actionoptions = {
                method: "POST",
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    _id: user._id,
                    role: user.role,
                    action:"/cart",
                    method:"GET",
                    output:`success to read the cart`
                })
            }
            const actionrawData = await fetch(actionurl, actionoptions)
            const actionjsonData = await actionrawData.json();
            console.log(actionjsonData)

            res.render('payforproduct.ejs',{
                cartproduct:user.cart,
                userData:user,
                total:totalPrice,
                message:""
            })
        } else {
            try {
                const ID = user._id;
                const url = `http://localhost:10888/user/cart/get/${ID}`;
                const rawData = await fetch(url);
                
                if (rawData.status !== 200) {
                    throw new Error("Failed to fetch database cart");
                }
                
                const jsonData = await rawData.json(); 
                for(let i=0; i<jsonData.cart.length; i++){
                    totalPrice = totalPrice + Number(jsonData.cart[i].price)
                }

                const actionurl = `http://localhost:10888/post/action`
                const actionoptions = {
                    method: "POST",
                    headers: { "content-type": "application/json" },
                    body: JSON.stringify({
                        _id: user._id,
                        role: user.role,
                        action:"/cart",
                        method:"GET",
                        output:`success to read the cart`
                    })
                }
                const actionrawData = await fetch(actionurl, actionoptions)
                const actionjsonData = await actionrawData.json();
                console.log(actionjsonData)

                res.render("payforproduct.ejs", { 
                    cartproduct: jsonData.cart || [], 
                    userData: user,
                    total:totalPrice,
                    message:""
                });

            } catch (err) {
                console.error('Error to access the product', err);

                const actionurl = `http://localhost:10888/post/action`
                const actionoptions = {
                    method: "POST",
                    headers: { "content-type": "application/json" },
                    body: JSON.stringify({
                        _id: user._id,
                        role: user.role,
                        action:"/cart",
                        method:"GET",
                        output:`fail to read the cart`
                    })
                }
                const actionrawData = await fetch(actionurl, actionoptions)
                const actionjsonData = await actionrawData.json();
                console.log(actionjsonData)

                res.status(500).send({ err: err.message || err.toString() });
            }
        }
});

app.get('/cleanthecart/', async(req,res)=>{
    const userRole = req.query.role
    let totalPrice = 0
    if(userRole === "guest"){
        user.cart = []

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/cleanthecart/",
                method:"GET",
                output:`success to clean the cart`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render('payforproduct.ejs',{
            cartproduct:user.cart,
            userData:user,
            total:totalPrice,
            message:""
        })
    }else{
        const ID = user._id;
        const cleanurl = `http://localhost:10888/user/cart/clean/${ID}`
        const rawData = await fetch(cleanurl);

        if (rawData.status !== 200) {
            throw new Error("Failed to fetch database cart");
        }
        const jsonData = await rawData.json()

        const url = `http://localhost:10888/user/cart/get/${ID}`

        const rawData1 = await fetch(url);
        if (rawData1.status !== 200) {
            throw new Error("Failed to fetch database cart");
        }

        const jsonData1 = await rawData1.json();

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/cleanthecart/",
                method:"GET",
                output:`success to clean the cart`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)


        res.render('payforproduct.ejs',{
            cartproduct:jsonData1.cart,
            userData:user, 
            total:0,
            message:""
        }
    )
    }
})

app.get('/pay/',async(req,res) =>{
    const userRole = req.query.role
    console.log(userRole)
    let totalPrice = 0
    if(userRole === "guest"){
        for(let i=0; i<user.cart.length; i++){
            totalPrice = totalPrice + Number(user.cart[i].price)
        }

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/pay/",
                method:"GET",
                output:`try to pay`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render('payforproduct.ejs',{
            cartproduct:user.cart,
            userData:user,
            total:totalPrice,
            message:"Please Login to complete the payment"
        })
    }else{
        //clean the cart
        const ID = user._id;
        const frist_get_url = `http://localhost:10888/user/cart/get/${ID}`
        const frist_rawData = await fetch(frist_get_url);
        if (frist_rawData.status !== 200) {
            throw new Error("Failed to fetch database cart");
        }
        const frist_jsonData = await frist_rawData.json()
        for(let i=0; i<frist_jsonData.cart.length; i++){
                    totalPrice = totalPrice + Number(frist_jsonData.cart[i].price)
        }
        console.log(`${ID}first time get the cart`)
        console.log(`the totle price of the products is: ${totalPrice}`)

        const tran_url = `http://localhost:10888/create/tran`
        const options = {
            method:"POST",
            headers: { 'content-type': 'application/json' },
            body:JSON.stringify({
                userid:ID,
                cart:frist_jsonData.cart,
                totalprice:totalPrice
                }
            )
        }

        const tran_rawData = await fetch(tran_url, options)
        if (tran_rawData.status !== 200) {
            throw new Error("Failed to create tran");
        }
        const tran_jsonData = await tran_rawData.json()



        const cleanurl = `http://localhost:10888/user/cart/clean/${ID}`
        const rawData = await fetch(cleanurl);

        if (rawData.status !== 200) {
            throw new Error("Failed to fetch database cart");
        }
        const jsonData = await rawData.json()

        const url = `http://localhost:10888/user/cart/get/${ID}`

        const rawData1 = await fetch(url);
        if (rawData1.status !== 200) {
            throw new Error("Failed to fetch database cart");
        }

        const jsonData1 = await rawData1.json();

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/pay/",
                method:"GET",
                output:`success to pay`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)


        res.render('payforproduct.ejs',{
            cartproduct:jsonData1.cart,
            userData:user, 
            total:0,
            message:"Thank you for your payment"
        })
    }
})

//product detail
app.get('/detail',async(req,res) =>{
    try{
        const productId = req.query.id
        console.log(productId)
        const url = `http://localhost:10888/detail/get/${productId}`

        const rawData = await fetch(url);

        if (rawData.status !== 200) throw `Failed to retrieve product detail`;
        const product = await rawData.json();

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/detail",
                method:"GET",
                output:`success to get the detail of the product ${productId}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render('productdetail.ejs',{productdetail:product, userData:user})
    }catch(err){
        console.error(`Error to access the product ${err}`)

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/detail",
                method:"GET",
                output:`fail to get the detail of the product ${productId}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)


        res.status(500).send({ err: err });
    }
});


//login
app.get('/login', async(req,res)=>{

    const actionurl = `http://localhost:10888/post/action`
    const actionoptions = {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
            _id: user._id,
            role: user.role,
            action:"/login",
            method:"GET",
            output:`try to get the login page`
        })
    }
    const actionrawData = await fetch(actionurl, actionoptions)
    const actionjsonData = await actionrawData.json();
    console.log(actionjsonData)


     res.render('login.ejs',{message:""})
})

app.get('/logout', async(req,res)=>{
    try{
        const url = `http://localhost:10888/products/get`;

        const rawData = await fetch(url);
        if (rawData.status !== 200) throw `Failed to retrieve products`;
        const jsonData = await rawData.json();

         //audio
        const speechurl = `http://localhost:1000/speech/main`;

        const speechrawData = await fetch(speechurl);
        if (speechrawData.status !== 200) throw `speech error`;
        const audioBlob = await speechrawData.blob();
        const arrayBuffer = await audioBlob.arrayBuffer();
        const buffer = Buffer.from(arrayBuffer);
        const base64Audio = buffer.toString('base64');

        //action
        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/logout",
                method:"GET",
                output:`${user._id} success to logout`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        user = {
            _id:"",
            username:"",
            password:"",
            email:"",
            cart:[],
            role:"guest"
        }

         res.render('project.ejs',{productData:jsonData, userData:user, ad:base64Audio})
    } catch(err){
        console.error(`Error to access the product ${err}`)

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/logout",
                method:"GET",
                output:`${user._id} fail to logout`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.status(500).send({ err: err.toString() });
    }
})


//login compare date with Mongo
app.post('/login-endpoint',async(req,res)=>{
    console.log("access to log in");
    try{

        const username = req.body.username
        const password = req.body.password

        //login
        const url = `http://localhost:10888/login/get?username=${username}&password=${password}`
        //
        const url1 = `http://localhost:10888/products/get`;
    
        const [rawData, rawData1] = await Promise.all([fetch(url), fetch(url1)]);

        if (rawData.status !== 200) throw `Failed to Login to ${username}`;
        if (rawData1.status !== 200) throw `Failed to access the products`;
        const [jsonData, jsonData1]= await Promise.all([rawData.json(), rawData1.json()]);

        console.log(`${jsonData._id} has been login`);

        const ID = jsonData._id
        const guestCartItems = [...user.cart]

        if (guestCartItems.length > 0) {
            await fetch(`http://localhost:10888/user/cart/addinit`, {
                method: 'POST',
                headers: { 'content-type': 'application/json' },
                body: JSON.stringify({
                     _id: ID,
                     product: guestCartItems,

                 })
            });
        }
        const url2 = `http://localhost:10888/user/cart/get/${ID}`
        const rawData2 = await fetch(url2)
        if (rawData2.status !== 200) throw new Error("Failed to fetch database cart");
        const jsonData2 = await rawData2.json()
        user = {
            _id: jsonData._id,
            username: jsonData.username,
            password: jsonData.password,
            email: jsonData.email,
            cart: jsonData2.cart,
            role: jsonData.role
        };
         //audio
        const speechurl = `http://localhost:1000/speech/main`;

        const speechrawData = await fetch(speechurl);
        if (speechrawData.status !== 200) throw `speech error`;
        const audioBlob = await speechrawData.blob();
        const arrayBuffer = await audioBlob.arrayBuffer();
        const buffer = Buffer.from(arrayBuffer);
        const base64Audio = buffer.toString('base64');

        //action
        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/login-endpoint",
                method:"POST",
                output:`${user._id} success to login`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        console.log(`[偵錯] 登入完成！目前購物車內總共有 ${user.cart.length} 樣商品`);

        res.render("project.ejs", { productData: jsonData1, userData: user, cartproduct: user.cart, ad:base64Audio});
    } catch (err) {
        console.error(`Error to access the product ${err}`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/login-endpoint",
                method:"POST",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
    
        res.render('login.ejs',{message:"Login Fail Please try again"})
    }
})

app.get('/creatAcc', async(req,res)=>{

    const actionurl = `http://localhost:10888/post/action`
    const actionoptions = {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
            _id: user._id,
            role: user.role,
            action:"/creatAcc",
            method:"GET",
            output:`try to get the create account page`
        })
    }
    const actionrawData = await fetch(actionurl, actionoptions)
    const actionjsonData = await actionrawData.json();
    console.log(actionjsonData)

    res.render('createAC.ejs',{message:""})
})

app.post('/creatAcc/create', async(req,res)=>{
    try{
        const userName = req.body.username
        const userEmail = req.body.email
        const passWord = req.body.password
        const confirmPassword = req.body.confirmPassword
        if (passWord !== confirmPassword){
            res.render('createAC.ejs',{message:"PASSWORD must be same as CONFIRM PASSWORD"})
        }
        const url = `http://localhost:10888/create/user`
        const options = {
            method: 'POST',
            headers: { 'content-type': 'application/json' },
            body: JSON.stringify({
                username: userName,
                email: userEmail,
                password:passWord
            })
        }
        const rawData = await fetch(url,options)
        if(rawData.status !== 200) throw `Failed to add user "${userName}"`
        const jsonData = await rawData.json(); // received and pass to next layer
        console.log(`User "${userName}" has been added`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/creatAcc/create",
                method:"POST",
                output:`success to create account with email: ${userEmail}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect('/login')

    }catch (err) {
        console.error(`Error to create account ${err}`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/creatAcc/create",
                method:"POST",
                output:` ${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
    
        res.render('createAC.ejs',{message:"PASSWORD must be same as CONFIRM PASSWORD"})
    }
    

})

//forgetpassword
app.get("/forgetPassword",async(req,res)=>{

    const actionurl = `http://localhost:10888/post/action`
    const actionoptions = {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
            _id: user._id,
            role: user.role,
            action:"/forgetPassword",
            method:"GET",
            output:`try to get the page of forget password`
        })
    }
    const actionrawData = await fetch(actionurl, actionoptions)
    const actionjsonData = await actionrawData.json();
    console.log(actionjsonData)

    res.render('forgetpassword.ejs',{message:""})
})

app.post("/user/forgetPassword/",async(req,res)=>{
    try{
        const email = req.body.email
        console.log(email)
        //find user
        const url = `http://localhost:10888/find/user/${email}`
        const rawData = await fetch(url)
        if(rawData.status !== 200) throw `Failed to find user "${email}"`
        console.log(`find user ${email}`)
        const jsonData = await rawData.json()

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/forgetPassword/",
                method:"POST",
                output:`success to find a user with email: ${email}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render('changepassword.ejs',{email:jsonData.email, message:""})

    }catch (err) {
        console.error(`Error to access the product ${err}`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/forgetPassword/",
                method:"POST",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
    
        res.render('forgetpassword.ejs',{message:"please inter the email again"})
    }
})

app.post("/user/changepassword/",async(req, res)=>{
    try{
        const password = req.body.newpassword
        const confirmPassword = req.body.confirmpassword
        const email = req.body.email
        if(password !== confirmPassword){
            res.render(changepassword.ejs,{email:email,message:"PASSWORD must be same as CONFIRM PASSWORD"})
        }
        const url = "http://localhost:10888/db/changepassword/"
        const options = {
            method: 'PUT',
            headers: { 'content-type': 'application/json' },
            body: JSON.stringify({
                email: email,
                password:password
            })
        }
        const rawData = await fetch(url, options)
        if(rawData.status !== 200) throw `user ${rawData} failed to change password `
        console.log(`find user ${rawData}`)
        const jsonData = await rawData.json();

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/changepassword/",
                method:"PUT",
                output:`success to change password email:${email}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
        
        res.redirect('/logout')

    }catch (err) {
        console.error(`Error to access the product ${err}`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/changepassword/",
                method:"PUT",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
    
        res.render("changepassword.ejs",{email:req.body.email,message:"PASSWORD must be same as CONFIRM PASSWORD"})
    }
})


app.get("/userlist",async(req, res)=>{
    try{
        const url = "http://localhost:10888/get/all/user"
        const rawData = await fetch(url)

        if(rawData.status !== 200) throw `can not get all users `

        const jsonData = await rawData.json()

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/userlist",
                method:"GET",
                output:`success to get all user`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        //console.log(jsonData)
        res.render("userlist.ejs",{userData:jsonData})
    }catch (err) {
        console.error(`Error to access the product ${err}`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/userlist",
                method:"GET",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
    
        res.redirect("/")
    }
})

app.get("/delete/user/:id", async(req,res)=>{
    try{
        const userID = req.params.id
        const url = `http://localhost:10888/db/delete/user/${userID}`
        const options ={
            method: 'DELETE',
            headers: { 'content-type': 'application/json' },
        }
        const rawData = await fetch(url, options)
        if(rawData.status !== 200) throw `failed to delete user ${userID}`
        console.log(`delete user`)
        const jsonData = rawData.json()
        console.log("user has been deleted")

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/delete/user/:id",
                method:"DELETE",
                output:`success to delete user: ${userID}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/userlist")
    }catch (err) {
        console.error(`Error to delete user`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/delete/user/:id",
                method:"DELETE",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
    
        res.redirect("/userlist")
    }
}
)

app.get("/user/detail/edit/:id", async(req,res)=>{
    try{

        const userID = req.params.id
        //find user with id version
        const url = `http://localhost:10888/find/userid/${userID}`
        const rawData = await fetch(url)
        if(rawData.status !== 200) throw `failed see the detail of user ${userID}`
        console.log(`find user`)
        const jsonData = await rawData.json()

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/detail/edit/:id",
                method:"GET",
                output:`success to get the detail of the user: ${userID}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
        
        res.render('userdetailedit.ejs',{userData:jsonData})


    }catch (err) {
        console.error(`Error to see user`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/detail/edit/:id",
                method:"GET",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)
    
        res.redirect("/userlist")
    }
})

app.post("/user/update/:id", async(req,res)=>{
    try{
        const id = req.params.id
        const username = req.body.username
        const email = req.body.email
        const role = req.body.role

        const url = `http://localhost:10888/update/user/admin`
        const options = {
            method: 'PUT',
            headers: { 'content-type': 'application/json' },
            body:JSON.stringify({
                id:id,
                username:username,
                email:email,
                role:role
            })
        }

        const rawData = await fetch(url,options)

        if(rawData.status !== 200) throw `failed change the detail of user ${id}`

        const jsonData = await rawData.json()

        console.log(jsonData)

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/update/:id",
                method:"PUT",
                output:`success to update the user ${id}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/userlist")

    }catch (err) {
        console.error(`Error to update user`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/update/:id",
                method:"PUT",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/userlist")
    }
})

app.get("/productlist", async(req,res)=>{
    try{

        const url = `http://localhost:10888/products/get`
        const rawData = await fetch(url)
        if(rawData.status !== 200) throw `failed to get the detail of product `
        const jsonData = await rawData.json()

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/productlist",
                method:"GET",
                output:`success to read the products list`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render("productlist.ejs",{productData:jsonData})

    }catch (err) {
        console.error(`Error to update user`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/productlist",
                method:"GET",
                output:`fail to read the products list`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)


        res.redirect("/")
    }
})

app.post("/products/delete/:id",async(req,res)=>{
    try{
        const productID = req.params.id
        const url = `http://localhost:10888/products/delete`
        console.log(productID)
        const options = {
            method:"DELETE",
            headers: { 'content-type': 'application/json' },
            body:JSON.stringify({
                id:productID
                }
            )
        }
        const rawData = await fetch(url, options)
        if(rawData.status !== 200) throw `failed to delete product ${productID}`
        const jsonData = await rawData.json()
        console.log(jsonData)

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/products/delete/:id",
                method:"DELETE",
                output:`success to delete the product ${productID}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/productlist")

    }catch (err) {
        console.error(`Error to delete product`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/products/delete/:id",
                method:"DELETE",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/productlist")
    }
})

app.get("/profile", async(req,res)=>{
    try{
        const ID = user._id
        const url = `http://localhost:10888/find/userid/${ID}`

        const rawData = await fetch(url)
        if(rawData.status !== 200) throw `failed to find user `
        const jsonData = await rawData.json()

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/profile",
                method:"GET",
                output:`user: ${ID} try to his/her read own data`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render("profile.ejs",{userData:jsonData})
    }catch (err) {
        console.error(`Error to reach the profile`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/profile",
                method:"GET",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/")
    }
})

app.get("/historylist", async(req,res)=>{
    try{
        const url = `http://localhost:10888/get/history`
        const rawData = await fetch(url)
        if(rawData.status !== 200) throw `failed to find user `
        const jsonData = await rawData.json()

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/historylist",
                method:"GET",
                output:`success to read the history list`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.render("historylist.ejs", {historyData:jsonData})

    }catch (err) {
        console.error(`Error to reach the profile`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/historylist",
                method:"GET",
                output:`fail to read the history list`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/")
    }
})

app.post("/user/updatedata/:id", async(req,res)=>{
    try{
        const id = req.params.id
        const username = req.body.username
        const email = req.body.email

        const url = `http://localhost:10888/update/user/user`
        const options = {
            method: 'PUT',
            headers: { 'content-type': 'application/json' },
            body:JSON.stringify({
                id:id,
                username:username,
                email:email
            })
        }

        const rawData = await fetch(url,options)

        if(rawData.status !== 200) throw `failed change the detail of user ${email}`

        const jsonData = await rawData.json()

        console.log(jsonData)

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/updatedata/:id",
                method:"PUT",
                output:`success to change the detail of user: ${email}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/")

    }catch (err) {
        console.error(`Error to update user`);

        const actionurl = `http://localhost:10888/post/action`
        const actionoptions = {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                _id: user._id,
                role: user.role,
                action:"/user/updatedata/:id",
                method:"PUT",
                output:`${err}`
            })
        }
        const actionrawData = await fetch(actionurl, actionoptions)
        const actionjsonData = await actionrawData.json();
        console.log(actionjsonData)

        res.redirect("/")
    }
})

app.listen(PORT,()=>{
    console.log(`Connected on port ${PORT}`)
})

