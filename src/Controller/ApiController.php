<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Tweet;
use App\Entity\User;

class ApiController extends AbstractController
{
    function getTweet($id) {
        // Obtenemos el tweet
        $entityManager = $this->getDoctrine()->getManager();
        $tweet = $entityManager->getRepository(Tweet::class)->find($id);
        // Si el tweet no existe devolvemos un error con código 404.
        if ($tweet == null) {
            return new JsonResponse([
                'error' => 'NO se ha encontrado el Tweet'
            ], 404);
        }
        // Creamos un objeto genérico y lo rellenamos con la información.
        $result = new \stdClass();
        $result->id = $tweet->getId();
        $result->date = $tweet->getDate();
        $result->text = $tweet->getText();
        // Para enlazar al usuario, añadimos el enlace API para consultar su información.
        $result->user = $this->generateUrl('api_get_user', [
            'id' => $tweet->getUser()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        // Para enlazar a los usuarios que han dado like al tweet, añadimos sus enlaces API.
        $result->likes = array();
        foreach ($tweet->getLikes() as $user) {
            $result->likes[] = $this->generateUrl('api_get_user', [
                'id' => $user->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        // Al utilizar JsonResponse, la conversión del objeto $result a JSON se hace de forma automática.
        return new JsonResponse($result);
      }

    function getTweetfonyUser($id) {
        // TODO
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if ($user == null) {
            return new JsonResponse([
                'error' => 'No se ha encontrado el User'
            ], 404);
        }
        
        $result = new \stdClass();
        $result->id = $user->getId();
        $result->name = $user->getName();
        $result->user_name = $user->getUserName();


       $result->tweets = array();
       foreach ($user->getTweets() as $tweet) {
           $result->tweets[] = $this->generateUrl('api_get_tweet', [
               'id' => $tweet->getId(),
           ], UrlGeneratorInterface::ABSOLUTE_URL);
       }

       $result->likes = array();
       foreach ($user->getLikes() as $tweet) {
           $result->likes[] = $this->generateUrl('api_get_tweet', [
               'id' => $tweet->getId(),
           ], UrlGeneratorInterface::ABSOLUTE_URL);
       }

        return new JsonResponse($result);
    }

    function index() {
        $result = array();
        $result['users'] = $this->generateUrl('api_get_users',array(),
                                              UrlGeneratorInterface::ABSOLUTE_URL);
        $result['tweets'] = $this->generateUrl('api_get_tweets',array(),
                                              UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($result);
      }

    function getTweets()
      {
         
          $entityManager = $this->getDoctrine()->getManager();
          $tweets = $entityManager->getRepository(Tweet::class)->findAll();
  
          
          if ($tweets == null) {
              return new JsonResponse([
                  'error' => 'No se han encontrados los Tweets'
              ], 404);
          }
  
         
          $results = new \stdClass();
          $results->count = count($tweets);
          $results->results = array();
  
         
          foreach ($tweets as $tweet) {
              $result = new \stdClass();
              $result->id = $tweet->getId();
              $result->url = $this->generateUrl('api_get_tweet', [
                  'id' => $result->id,
              ], UrlGeneratorInterface::ABSOLUTE_URL);
  
              array_push($results->results, $result);
          }
  
          // Devolvemos el resultado en formato JSON
          return new JsonResponse($results);
      }

    function getTweetfonyUsers()
    {
      
        $entityManager = $this->getDoctrine()->getManager();
        $users = $entityManager->getRepository(User::class)->findAll();

      
        if ($users == null) {
            return new JsonResponse([
                'error' => 'No se han encontrado los Users'
            ], 404);
        }

     
        $results = new \stdClass();
        $results->count = count($users);
        $results->results = array();

     
        foreach ($users as $user) {
            $result = new \stdClass();
            $result->name = $user->getName();
            $result->url = $this->generateUrl('api_get_user', [
                'id' => $user->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            array_push($results->results, $result);
        }

      
        return new JsonResponse($results);
    }

    function postTweet(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($request->request->get("userId"));
        if ($user == null) {
            return new JsonResponse([
                'error' => 'User not found'
            ], 404);
        }

        $tweet = new Tweet();
        $tweet->setText($request->request->get("text"));
        $tweet->setDate(new \DateTime());
        $tweet->setUser($user);
        $entityManager->persist($tweet);
        $entityManager->flush();

      
        $result = new \stdClass();
        $result->id = $tweet->getId();
        $result->date = $tweet->getDate();
        $result->text = $tweet->getText();
        $result->user = $this->generateUrl('api_get_user', [
            'id' => $user->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL); 
        $result->likes = array(); 

        return new JsonResponse($result, 201);
    }

    function postTweetfonyUser(Request $request) {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['userName' => $request->request->get("userName")]);
        if ($user) {
          return new JsonResponse([
            'error' => 'UserName already exists'
          ], 409);
        }
        $user = new User();
        $user->setName($request->request->get("name"));
        $user->setUserName($request->request->get("userName"));
        $entityManager->persist($user);
        $entityManager->flush();
        $result = new \stdClass();
        $result->id = $user->getId();
        $result->name = $user->getName();
        $result->userName = $user->getUserName();
        $result->likes = array(); 
        $result->tweets = array(); 

        return new JsonResponse($result, 201);
      }

      function putTweetfonyUser(Request $request, $id) {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);
        if ($user == null) {
          return new JsonResponse([
            'error' => 'User not found'
          ], 404);
        }
        if ($user->getUserName() != $request->request->get("userName")) {
            $user2 = $entityManager->getRepository(User::class)->findOneBy(['userName' => $request->request->get("userName")]);
            if ($user2) {
            return new JsonResponse([
                'error' => 'UserName already in use'
            ], 409);
            }
        }
        $user->setName($request->request->get("name"));
        $user->setUserName($request->request->get("userName"));
        $entityManager->flush();
        $result = new \stdClass();
        $result->id = $user->getId();
        $result->name = $user->getName();
        $result->userName = $user->getUserName();
        $result->likes = array();
        foreach ($user->getLikes() as $tweet) {
          $result->likes[] = $this->generateUrl('api_get_tweet', [
            'id' => $tweet->getId(),
          ], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        $result->tweets = array();
        foreach ($user->getTweets() as $tweet) {
          $result->tweets[] = $this->generateUrl('api_get_tweet', [
            'id' => $tweet->getId(),
          ], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        return new JsonResponse($result);
      }

      function putTweet(Request $request, $id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $tweet = $entityManager->getRepository(Tweet::class)->find($id);
        if ($tweet == null) {
            return new JsonResponse([
                'error' => 'Tweet not found'
            ], 404);
        }
        
        $tweet->setText($request->request->get("text"));
        $tweet->setDate(new \DateTime());
        $entityManager->flush();

      
        $result = new \stdClass();
        $result->id = $tweet->getId();
        $result->date = $tweet->getDate();
        $result->text = $tweet->getText();
        $result->user = $this->generateUrl('api_get_user', [
            'id' => $tweet->getUser()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $result->likes = array();
        foreach ($tweet->getLikes() as $user) {
            $result->likes[] = $this->generateUrl('api_get_user', [
                'id' => $user->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new JsonResponse($result);
    }

    function deleteTweet($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $tweet = $entityManager->getRepository(Tweet::class)->find($id);
        if ($tweet == null) {
            return new JsonResponse([
                'error' => 'Tweet not found'
            ], 404);
        }
        
        $entityManager->remove($tweet);
        $entityManager->flush();

        
        return new JsonResponse(null, 204);
    }

    function deleteTweetfonyUser($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);
        if ($user == null) {
            return new JsonResponse([
                'error' => 'User not found'
            ], 404);
        }
        
        $entityManager->remove($user);
        $entityManager->flush();

     
        return new JsonResponse(null, 204);
    }
      
}