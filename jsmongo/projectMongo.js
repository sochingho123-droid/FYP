'use strict'
import { MongoClient, ServerApiVersion, ObjectId } from 'mongodb'
import express from "express";
import helmet from 'helmet'

const app = express();
const PORT = 10888

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use(
  helmet.contentSecurityPolicy({
    directives: {
      ...helmet.contentSecurityPolicy.getDefaultDirectives(),
      "connect-src": [
        "'self'", 
        "http://localhost:3000", 
        "ws://localhost:3000",
        "http://me.kes.v2.scr.kaspersky-labs.com", 
        "ws://me.kes.v2.scr.kaspersky-labs.com"
      ],
    },
  })
);

const url = "mongodb://127.0.0.1:27017"
let db, usersCollection,productCollection, actionCollection, tranCollection, dbName = 'projectdb'


app.get('/products/get', async(req,res) =>{
    console.log("Retrieving all products")
    try{
        productCollection = db.collection('products')

        const result = await productCollection.find().toArray()

        if(!result ||result === null) throw "no product find"

        res.status(200).json(result)
        console.log("Successfully retrieved products")
    } catch(err){
        console.error(`Error to access the product ${err}`)
        res.status(500).send({ err: err });
    }
});

app.get('/product/get/:keyword', async(req,res) =>{
    console.log("Retrieving products with keyword")
    try{
        const keyword = req.params.keyword
        productCollection = db.collection('products')
        const searchRegex = new RegExp(keyword, 'i');

        const result = await productCollection.find({$or:[{mtype:searchRegex},{name:searchRegex}]}).toArray()

        if(!result) throw "no product find"

        res.status(200).json(result)
        console.log("Successfully retrieved products")
    } catch(err){
        console.error(`Error to access the product ${err}`)
        res.status(500).send({ err: err });
    }
});

app.get("/detail/get/:id",async(req,res) =>{
    try{
        const products = db.collection('products')

        const productId = req.params.id

        const product = await products.findOne({pid:productId})

        if (!product) throw "no product find in the cart";

        res.status(200).json(product);
        console.log("Successfully retrieved cart products");

        
    }catch(err){
        console.error(`Error to access the product ${err}`)
        res.status(500).send({ err: err });
    }
});

app.get('/search/get', async(req,res)=>{
    try{
        const productCollection = db.collection('products')
        const {search} = req.query
        const searchRegex = new RegExp(search, 'i');

        const productResults = await productCollection.find({
            $or: [
                { name: searchRegex },
                { type: searchRegex }
            ]
        }).toArray();
        if (productResults.length === 0) {
            return res.status(404).json({ err: "No products found" });
        }

        res.status(200).json(productResults);
    } catch (err) {
        console.error(`Error to access the product ${err}`);
    
        res.status(500).send({ err: err.message || err.toString() });
    }
})

app.get('/login/get', async(req,res) =>{
    console.log("log in")
    try{
        const usersCollection = db.collection('users')

        const username = req.query.username
        const password = req.query.password
        console.log(`${username} try to login with password: ${password} (型態: ${typeof password})`);

        const result = await usersCollection.findOne({
            $or:[
                {
                    username:username,
                    password: password
                },{
                    email:username,
                    password: password
                }
            ]
            
        });

        if(!result ||result === null) throw "user not found"

        res.status(200).json(result)
        console.log("Successfully login")
    } catch(err){
        console.error(`Error to access the  ${err}`)
        res.status(500).send({ err: err });
    }
});

app.post('/user/cart/add', async(req,res) =>{
    try {
        const { _id, product } = req.body
        

        const usersCollection = db.collection('users')
        
        console.log(`add ${product.brand}${product.name}`)

        const result = await usersCollection.updateOne(
            { _id: new ObjectId(_id) },
            { $push: { cart:product} }
        )

        if (!result) throw "Cannot insert item"

        // Same to d4.
        res.status(200).json(result)
        console.log(`Successfully inserted item with  ${result}`)
    } catch (err) {
        console.error(`Failed to insert item: ${err}`)
        res.status(500).send({ err: err })
    }
})

app.post('/user/cart/addinit', async(req,res) =>{
    try {
        const { _id, product } = req.body
        

        const usersCollection = db.collection('users')
        
        console.log(`add ${product.brand}${product.name}`)

        const result = await usersCollection.updateOne(
            { _id: new ObjectId(_id) },
            { $push: { cart: { $each: product } } }
        )

        if (!result) throw "Cannot insert item"

        res.status(200).json(result)
        console.log(`Successfully inserted item with  ${result}`)
    } catch (err) {
        console.error(`Failed to insert item: ${err}`)
        res.status(500).send({ err: err })
    }
})


app.get('/user/cart/get/:id', async(req, res) => {
    try {
        const userID = req.params.id; 
        const usersCollection = db.collection('users');

        const result = await usersCollection.findOne({ _id: new ObjectId(userID) });

        if (!result) throw "no product find in the cart";

        res.status(200).json(result);
        console.log("Successfully retrieved cart products");
    } catch(err) {
        console.error('Error to access the product', err);
        res.status(500).send({ err: err.toString() });
    }
});

app.post("/user/cart/delete", async(req, res) => {
    try {
        const {_id, pid}= req.body

        const usersCollection = db.collection('users');

        const result = await usersCollection.updateMany(
            { _id: new ObjectId(_id) },
            { $pull: { cart:{pid:pid}} }
        )
        

        if (!result) throw "no product find in the cart";

        res.status(200).json(result);
        console.log("Successfully retrieved cart products");
    } catch(err) {
        console.error('Error to access the product', err);
        res.status(500).send({ err: err.toString() });
    }
});

app.get("/user/cart/clean/:id",async(req, res) => {
    try{
        const userID = req.params.id

        const usersCollection = db.collection('users');

        const result = usersCollection.updateOne(
            { _id: new ObjectId(userID) },
            { $set: { cart: [] } })
            if (!result) throw "no product find in the cart";

            res.status(200).json(result);
            console.log("Successfully retrieved cart products");
    } catch(err) {
        console.error('Error to access the product', err);
        res.status(500).send({ err: err.toString() });
    }
})

app.post("/create/user",async(req,res) =>{
    try{
        const {username, email, password}= req.body
        const usersCollection = db.collection('users');
    
        const newUser = {
            username:username,
            password:password,
            email:email,
            cart:[],
            role:"user"
        }

        const result = await usersCollection.insertOne(newUser)
        if (!result.insertedId) throw "Cannot insert user"

        res.status(200).json(result)
        console.log(`Successfully inserted item with _id: ${result.insertedId}`)
    }catch(err) {
        console.error(`Failed to insert user: ${err}`)
        res.status(500).send({ err: err })
    }
})

//find the user is on the list
app.get("/find/user/:email",async(req,res)=>{
    try{
        const email = req.params.email
        const usersCollection = db.collection("users")
        const result = await usersCollection.findOne({email:email})
        console.log(result)
        if (!result) throw `user ${email} find in the list`;

        res.status(200).json(result);
        console.log(`${email} try to change password`);
    }catch(err) {
        console.error(`Failed to find user: ${err}`)
        res.status(500).send({ err: err })
    }
})

app.put("/db/changepassword/",async(req,res)=>{
    try{
        const email = req.body.email
        const password = req.body.password

        const usersCollection = db.collection("users")

        const result = await usersCollection.updateOne(
            {
                email:email
            },{
                $set:{
                    password:password
                }
            },{
                 upsert: false 
            } 
        )
        if (!result) throw `user ${email} fail to update password`;
        res.status(200).json(result);
        console.log(`${email} password changed`);
    }catch(err) {
        console.error(`Failed to find user: ${err}`)
        res.status(500).send({ err: err })
    }
})

//get all user
app.get("/get/all/user",async(req, res)=>{
    try{
        const usersCollection = db.collection('users')
        const result = await usersCollection.find().toArray()
        if (!result) throw `fail to get all users`;

        res.status(200).json(result);
        console.log(`get all users`);

    }catch(err) {
        console.error(`Failed get all user: ${err}`)
        res.status(500).send({ err: err })
    }
})

app.delete("/db/delete/user/:id", async(req, res)=>{
    try{
        const usersCollection = db.collection('users')
        const userID = req.params.id
        console.log(`Deleting user ${userID}`)

        let regexp = /^[0-9a-fA-F]+$/
        if (!regexp.test(userID)) throw new Error("Invalid hexadecimal user ID format")
        
        const options = { writeConcern: { w: 1, j: true, wtimeout: 1000 } }
        const result = await usersCollection.deleteOne(
            {
                _id: new ObjectId(userID)
            },options
        )

        if (result.deletedCount === 0) throw 'Cannot delete user or user does not exist'

        res.status(200).json(result)
        console.log(`Successfully deleted user with ID: ${userID}`)
    }catch(err) {
        console.error(`Failed delete user: ${err}`)
        res.status(500).send({ err: err })
    }
})

app.get("/find/userid/:id",async(req,res)=>{
    try{
        const id = req.params.id
        const usersCollection = db.collection("users")
        const result = await usersCollection.findOne({_id:new ObjectId(id)})
        if (!result) throw `user ${id} find in the list`;

        res.status(200).json(result);
        console.log(`try to read the detail of ${id}`);
    }catch(err) {
        console.error(`Failed to find user: ${err}`)
        res.status(500).send({ err: err })
    }
})

app.put("/update/user/admin", async(req,res)=>{
    try{
        const usersCollection = db.collection("users")
        const id = req.body.id
        const username = req.body.username
        const email = req.body.email
        const role = req.body.role

        const result = await usersCollection.updateOne(
            {
                _id: new ObjectId(id)
            },{
                $set:{
                    username:username,
                    email:email,
                    role:role
                }
            }
        )

        if (!result) throw `user ${id} update fail`;

        res.status(200).json(result);
        console.log(`try to read the detail of ${id}`);

        
    }catch(err) {
        console.error(`Failed to change user detail: ${err}`)
        res.status(500).send({ err: err })
    }
})

app.delete("/products/delete", async(req,res)=>{
    try{

        const productCollection = db.collection('products')

        const productID = req.body.id
        let regexp = /^[0-9a-fA-F]+$/
        if (!regexp.test(productID)) throw new Error("Invalid hexadecimal user ID format")

        const options = { writeConcern: { w: 1, j: true, wtimeout: 1000 } }
        console.log(productID)
        const result = await productCollection.deleteOne(
            {
                _id: new ObjectId(productID)
            },options
        )
        if (!result) throw `delete product ${productID} fail`;
        if (result.deletedCount === 0) throw 'Cannot delete product does not exist'

        res.status(200).json(result);
        console.log(`product ${productID} has been deleted `);

    }catch(err) {
        console.error(`Failed to delete product: ${err}`)
        res.status(500).send({ err: err })
    }
})

app.post("/create/tran", async(req,res) =>{
    try{
        
        const tranCollection = db.collection("trans")
        const {userid, cart, totalprice} = req.body

        const result = await tranCollection.insertOne(
            {
                userid:userid,
                products:cart,
                totalprice:totalprice
            }
        )
        if (!result) throw `create tran fail`;
        res.status(200).json(result);
        console.log(`tran has been created `);

    }catch(err) {
        console.error(`Failed to create tran: ${err}`)
        res.status(500).send({ err: err })
    }
})

app.put("/update/user/user", async(req,res)=>{
    try{
        const usersCollection = db.collection("users")
        const id = req.body.id
        const username = req.body.username
        const email = req.body.email

        const result = await usersCollection.updateOne(
            {
                _id: new ObjectId(id)
            },{
                $set:{
                    username:username,
                    email:email
                }
            }
        )

        if (!result) throw `user ${id} update fail`;

        res.status(200).json(result);
        console.log(`try to read the detail of ${id}`);

        
    }catch(err) {
        console.error(`Failed to change user detail: ${err}`)
        res.status(500).send({ err: err })
    }
})

//action post
/*app.post("/post/action", async(req,res)=>{
    try{
        
        const {_id,role, action, method,output} = req.body
        const isValidObjectId = typeof _id === 'string' && _id.length === 24 && /^[0-9a-fA-F]{24}$/.test(_id);
        const actionCollection = db.collection("actions")


        const result = await actionCollection.insertOne(
            {
                userid:isValidObjectId ? new ObjectId(_id) : _id,
                user_role:role,
                action:action,
                method:method,
                output:output
            }
        )
        if (!result) throw `create action fail`;
        res.status(200).json(result);
        console.log(`action has been created `);

    }catch(err) {
        console.error(`Failed to add action to db: ${err}`)
        res.status(500).send({ err: err })
    }
})*/

app.get("/get/history", async(req,res)=>{
    try{
        const actionCollection = db.collection("actions")

        const result = await actionCollection.find().toArray()
        if (!result) throw `get action fail`;
        res.status(200).json(result);
        console.log(`action has been get `);

    }catch(err) {
        console.error(`Failed to get action from db: ${err}`)
        res.status(500).send({ err: err })
    }
})

async function startServer(){
    try{
        const client = new MongoClient(url,{
            serverApi:{
                version: ServerApiVersion.v1,
                strict:true,
                deprecationErrors:true
            }
        });
        await client.connect()
        db = client.db(dbName)
        usersCollection = db.collection('users')
        productCollection = db.collection('products')
        tranCollection = db.collection('trans')
        actionCollection = db.collection("actions")

        console.log(`Success connect to db`);

    }catch(error){
        console.error("Connect fail to", error)
        process.exit()
    }

    app.listen(PORT,()=>{
        console.log(`Connected on port ${PORT}`)
    })
}
startServer()
