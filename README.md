# web-sdk-conference-samples
Sample demo Stringee video conference
## Demo
https://v2.stringee.com/web-sdk-conference-samples/test_room.html

### Step 1: Preparation

1. In order to use Stringee Video Api you have to have a Stringee account. If you haven't got one, please sign up at  https://developer.stringee.com/account/register
2. Create a project on https://developer.stringee.com/project




### Step 2: Room management APIs
0. Follow this guide to generate an access token to call the rest api https://developer.stringee.com/docs/call-rest-api/call-rest-api-authentication
(For testing purposes, you can go to Dashboard -> Tools -> Generate Access token. (Authentication: choose Rest API Authentication)


1. Create room (change ACCESS_TOKEN = access token generated from the previous step)
```bash
curl -i -X POST \
   -H "Content-Type:application/json" \
   -H "X-STRINGEE-AUTH:ACCESS_TOKEN" \
   -d \
'{
  "name": "tient_test",
  "uniqueName": "tient_test"
}' \
 'https://api.stringee.com/v1/room2/create'
 ```
 
2. List room
```bash
 curl -i -X GET \
   -H "X-STRINGEE-AUTH:ACCESS_TOKEN" \
 'https://api.stringee.com/v1/room2/list'
```

3. Delete room
```bash
curl -i -X PUT \
   -H "X-STRINGEE-AUTH:ACCESS_TOKEN" \
   -H "Content-Type:application/json" \
   -d \
'{
  "roomId": "room-vn-1-EKNUBSUJFW-1597685448625"
}' \
 'https://api.stringee.com/v1/room2/delete'
```




### Step 3. Intergrate sdk to the website

0. Generate roomtoken with the following jwt format and send it to the client to join the room (for details, refer to the file php\token_pro.php in the attached sample)

```
HEADER:
    {
        "typ": "JWT",
        "alg": "HS256",// only support HS256
        "cty": "stringee-api;v=1"
    }

PAYLOAD:
    {
        "jti": "SK...-...",//JWT ID
        "iss": "SK...",//API key sid
        "exp": ...,//expiration time
        "roomId": ""room-...", //Your room id
		'permissions' => array(
            'publish' => true, // allow publish
            'subscribe' => true,//allow subcribe
            'control_room' => true, //allow control
        )
    }

VERIFY SIGNATURE:
    HMACSHA256(
        base64UrlEncode(HEADER) + "." +
        base64UrlEncode(PAYLOAD),
        apiKeySecret
    )
    
```
	
1. Add Stringee SDK to your source
```js
<script type="text/javascript" src="js/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="https://cdn.stringee.com/sdk/web/latest/stringee-web-sdk.min.js"></script>
```


2. Generate access token to connect to StringeeServer
- For testing purposes, you can go to Dashboard -> Tools -> Generate Access token and generates an access_token.
- For production environment please refer to the following guide https://developer.stringee.com/docs/client-authentication


3. Connection
```js
var stringeeClient;
client.connect(access_token);
```

Events
- When connecting to StringeeServer
```js
client.on('connect', function () {
    console.log('connected');
});
```

- When the authentication is successful
```js
client.on('authen', function (res) {
    console.log('authen', res);
    $('#loggedUserId').html(res.userId);
});
```

- When disconnecting from StringeeServer
```js
client.on('disconnect', function () {
    console.log('disconnected');
});
```



4. Join room
```js
var videoDimensions = $('#videoDimensions').val();
console.log('videoDimensions: ' + videoDimensions);
if (videoDimensions == '720p') {
	videoDimensions = {
		width: {
			min: "1280",
			max: "1280"
		},
		height: {
			min: "720",
			max: "720"
		}
	};
} else if (videoDimensions == '480p') {
	videoDimensions = {
		width: {
			min: "854",
			max: "854"
		},
		height: {
			min: "480",
			max: "480"
		}
	};
} else if (videoDimensions == '360p') {
	videoDimensions = {
		width: {
			min: "640",
			max: "640"
		},
		height: {
			min: "360",
			max: "360"
		}
	};
} else if (videoDimensions == '240p') {
	videoDimensions = {
		width: {
			min: "426",
			max: "426"
		},
		height: {
			min: "240",
			max: "240"
		}
	};
}

var pubOptions = {
	audio: true,
	video: true,
	screen: screenSharing,//screenSharing = true if the user is sharing their screen, = false if the user is joining the room
	videoDimensions: videoDimensions
};

StringeeVideo.createLocalVideoTrack(stringeeClient, pubOptions).then(function (localTrack1) {
	console.log('create Local Video Track success: ', localTrack1);
	localTracks.push(localTrack1);

	//play local video
	var videoElement = localTrack1.attach();
	videoElement.setAttribute("style", "width: 300px;background: black;padding: 5px;height: 200px;margin: 5px");
	videoElement.setAttribute("controls", "true");
	videoElement.setAttribute("playsinline", true);
	document.body.appendChild(videoElement);

	StringeeVideo.joinRoom(stringeeClient, roomToken).then(function (data) {
		console.log('join room success data: ', data);
		$('#shareScreenBtn').removeAttr('disabled');
		$('#leaveBtn').removeAttr('disabled');

		$('#muteBtn').removeAttr('disabled');
		$('#disableVideoBtn').removeAttr('disabled');

		$('#joinBtn').attr('disabled', 'disabled');

		room = data.room;

		// room events
		room.clearAllOnMethos();
		
		// joinroom event
		room.on('joinroom', function (event) {
			console.log('on join room: ' + JSON.stringify(event.info));
		});
		
		// leave room event
		room.on('leaveroom', function (event) {
			console.log('on leave room: ' + JSON.stringify(event.info));
		});
		
		// message event
		room.on('message', function (event) {
			console.log('on message: ' + JSON.stringify(event.info));
		});
		
		// add track event when other people join the room
		room.on('addtrack', function (event) {
			console.log('on add track: ' + JSON.stringify(event.info));
			var local = false;
			localTracks.forEach(function (localTrack2) {
				if (localTrack2.serverId === event.info.track.serverId) {
					console.log(localTrack2.serverId + ' is LOCAL');
					local = true;
				}
			});
			if (!local) {
				subscribe(event.info.track);
			}
		});
		
		// remove track event when other people leave the room
		room.on('removetrack', function (event) {
			console.log('on remove track', event);
			var track = event.track;
			if (!track) {
				return;
			}

			var mediaElements = track.detach();
			mediaElements.forEach(function (videoElement) {
				videoElement.remove();
			});
		});

		//publish our own track into the room
		room.publish(localTrack1).then(function () {
			console.log('publish Local Video Track success: ' + localTrack1.serverId);
		}).catch(function (error1) {
			console.log('publish Local Video Track ERROR: ', error1);
		});

		//subscribe to videos of people who have joined before
		data.listTracksInfo.forEach(function (trackInfo) {
			subscribe(trackInfo);
		});
	}).catch(function (res) {
		console.log('join room ERROR: ', res);
	});
}).catch(function (res) {
	console.log('create Local Video Track ERROR: ', res);
	showStatus(res.name + ": " + res.message);
});
```