**free
      
      // Programme de test de la passerelle PHP vers IBM i.
      // Aucune lecture ou ecriture de donnees metier.
      //
      // DFTACTGRP(*NO) : programme ILE hors du groupe par defaut.
      // ACTGRP(*CALLER) : utiliser le groupe d'activation de l'appelant.
      ctl-opt dftactgrp(*no)
              actgrp(*caller)
              option(*srcstmt : *nodebugio);
      
      // Interface d'entree du programme.
      //
      // Les parametres sont passes par reference.
      // CONST indique que le programme ne modifie pas request.
      // Les tailles devront correspondre exactement a celles
      // de la future procedure externe SQL.
      dcl-pi *n;
        request char(10) const;
        reply char(30);
        resultCode char(5);
      end-pi;
      
      // Toujours initialiser les sorties pour chaque appel.
      // CLEAR remplit le champ alphanumerique reply avec des espaces.
      clear reply;
      resultCode = '00000';
      
      // Les champs CHAR ont une taille fixe.
      // TRIM retire les espaces de remplissage pour la comparaison.
      if %trim(request) = 'PING';
        
        // Reponse normale, sans effet sur les donnees.
        reply = 'PONG';
        
      else;
        
        // Refus applicatif controle.
        // Le programme retourne normalement avec un code explicite.
        resultCode = 'B0001';
        reply = 'Unsupported request';
      endif;
      
      // Terminer le programme et liberer son etat pour l'appel suivant.
      *INLR = *on;
      return;