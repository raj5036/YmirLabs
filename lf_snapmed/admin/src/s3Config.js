import { getSignedUrl } from '@aws-sdk/s3-request-presigner';
import { S3Client, GetObjectCommand } from '@aws-sdk/client-s3';

async function presignedUrl (key, folder) {
    const client = new S3Client({
        region: process.env.VUE_APP_S3_REGION,
        credentials: {
            accessKeyId: process.env.VUE_APP_S3_ACCES_KEY,
            secretAccessKey: process.env.VUE_APP_S3_ACCESS_SECRET
        }
    });
    const command = new GetObjectCommand({
        Bucket: process.env.VUE_APP_S3_BUCKET_NAME,
        Key: folder + key
    });

    let url = await getSignedUrl(client, command, { expiresIn: 3600 });
    return url;
}

export default presignedUrl;
